<?php

namespace App\Services;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Зарегистрировать нового пользователя.
     *
     * При успешном создании объект пользователя кэшируется на 1 минуту
     * (ключ — его e-mail) для ускорения последующей авторизации.
     *
     * @param  RegisterRequest  $data
     * @return User                         Созданный пользователь
     */
    public function register(RegisterRequest $data)
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => bcrypt($data->password),
        ]);

        Cache::add($user->email, $user, now()->addMinutes(1));

        return $user;
    }

    /**
     * Авторизовать пользователя и выдать токен Sanctum.
     *
     * Алгоритм:
     *  1. Пытаемся получить пользователя из кэша (живёт 1 мин).
     *  2. Если нет в кэше — ищем в базе.
     *  3. Проверяем пароль. При несовпадении — бросаем `ValidationException`.
     *  4. Возвращаем plain-text токен.
     *
     * @param  LoginRequest  $request
     * @return string                        Plain-text токен (`$token->plainTextToken`)
     *
     * @throws ValidationException           Неверные учётные данные
     */
    public function login(LoginRequest $request){
        if(Cache::has($request->email)){
            $user = Cache::get($request->email);
        } else{
            $user = User::where('email', $request->email)->first();
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user->createToken('api-token')->plainTextToken;
    }

    /**
     * Выйти из системы (удалить текущий токен Sanctum).
     *
     * @param  Request  $request
     * @return bool|int                    `true`/`1`, если токен удалён
     */
    public function logout(Request $request){
        return $request->user()->currentAccessToken()->delete();
    }
}
