# Mejorar la gestión de errores de la API

Dirígete a `src/Store/LemonSqueezyApi.php`. Probablemente recuerdes este método`createCheckoutUrl()` de antes. Este lanzamiento a `string` solucionaba un error. Elimínalo temporalmente para que podamos volver a recuperar ese error. De vuelta en tu navegador, haz clic en "Añadir a la cesta", luego en "Pagar con LemonSqueezy", y... veremos nuestro esperado`ClientException`.

Anteriormente, utilizamos este truco de `dd($response->getContent(false))` para ver los detalles detrás de `ClientException`. Descomenta esta línea, actualiza la página y... ahora podemos ver el error real.

## Hacer que los mensajes de error sean más informativos

Esto está bien, pero seguro que podríamos mejorarlo aún más. En lugar de utilizar `dd()`para depurar, probemos a envolver el método `client->request()` en otro método. En la parte inferior de esta clase, crea un `private function` llamado `request()`que devuelva un `array`.

Su primer argumento será `string $method`, seguido de `string $url` y una matriz de opciones. Ahora viene la parte divertida: Abre un bloque `try-catch` y, en el `try`, escribe `$response = $this->client->request()` y pasa todas las variables - `$method`, `$url`, y `$options`. Crea una variable `$data` que sea igual a `$response->toArray()`. Vamos a `catch` `ClientException $e` y dentro, haz un poco de magia.

En la parte inferior, `return $data`, y de nuevo en `catch`, queremos el contenido de la respuesta en bruto, así que escribe `$data = $e->getResponse()->toArray()` y pasa `false` como primer argumento. También añadiremos aquí `dd($data)` temporalmente para que podamos ver la respuesta de error de la API.

A continuación, actualiza el método `createCheckoutUrl()`. En lugar de`$this->client->request()`, utiliza sólo `$this->request()`, pasando todos los mismos argumentos. Si nos dirigimos e intentamos comprobarlo de nuevo... ¡boom! Esto es un volcado correcto de la petición real a la API como una matriz.

## Crear mensajes de error útiles

Bien, en lugar de "volcar y morir", vamos a elaborar algunos mensajes de error que sean más útiles. En nuestro código, busca el método `request()`. Comenta esta sentencia `dd()`y debajo, añade `$mainErrorMessage = 'LS API Error:'`. Ahora, comprobemos si tenemos un error con `$error = $data['errors'][0] ?? null`. Si lo hay,`error`, haz otra comprobación con `if (isset($error['status'])`. Dentro, escribe `$mainErrorMessage .= ' ' . $error['status']`. Haz lo mismo con `title`,`detail`, y `source.pointer`. Iré más rápido en esta parte. Por último, `else`, y dentro, añade el contenido en bruto con`$mainErrorMessage .= $e->getResponse()->getContent(false)`. Perfecto

Al final, `throw new \Exception()` con `$mainErrorMessage`, `0`como segundo argumento, y `$e` como tercer argumento. Esto establece la excepción original como la anterior, lo que ayuda aún más a la depuración ¡Eso es! Se trata de un patrón bastante común y útil para simplificar excepciones complejas, pero sin dejar de proporcionar una referencia a la original.

¡Vamos a probarlo!

En la página de pago, actualiza y... ¡voilá! El mensaje de error genérico es ahora un mensaje personalizado:

> Error de API LS: código de estado 422: Entidad no procesable. el campo {0} debe ser una
> cadena (en la ruta data.attributes.checkout.data.custom.user_id).

Eso era mucho más fácil de entender.

Todo lo que tenemos que hacer ahora es devolver la tipificación `string` en la línea `user_id`. Ya no necesitamos esta línea `$response->toArray()`, así que podemos eliminarla junto con la `dd()`. Sustituye también la variable `$response` por `$lsCheckout`, puesto que ya tenemos aquí una matriz de datos del objeto checkout.

Vuelve a actualizar la página para ver si funciona, y... ¡ya está!

El último paso es sustituir todas las llamadas a `$this->client->request()` restantes por`$this->request()`. Haré esto rápidamente para `retrieveStoreUrl()`,`listOrders()`, y eliminaré las llamadas a `$response->toArray()` mientras estoy en ello.

Si probamos nuestro sitio una vez más... ¡la página de cuenta sigue funcionando... y también la página de pago! Nuestro proceso de tratamiento de errores es ahora eficaz e informativo.

Siguiente paso: Mejoremos la experiencia de pago de nuestros clientes incrustando la página de pago de LemonSqueezy en nuestra aplicación.
