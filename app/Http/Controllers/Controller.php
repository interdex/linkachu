<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="LINKACHU API",
 *     version="1.0.0",
 *     description="
 **Документация API LINKACHU**

Для работы методов необходим **access token**. Если у вас уже есть аккаунт – выполните `POST /api/login` и получите токен. Если аккаунта нет – зарегистрируйтесь через `POST /api/register`, затем выполните `POST /api/login`. В ответе придёт ваш токен.

В Swagger UI нажмите **Authorize**, вставьте **только** полученный токен в поле **Value** и нажмите **Authorize**. После этого все защищённые методы будут доступны.
"
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Production server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Введите токен без префикса Bearer"
 * )
 */
abstract class Controller
{
}
