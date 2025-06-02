# Mejorar la gestión de errores de la API

Dirígete a `src/Store/LemonSqueezyApi.php`. Probablemente recuerdes esta función `createCheckoutUrl()` de antes. Este `string` arrojaba un error antes, así que elimínalo temporalmente. Guárdalo y, de vuelta en tu navegador, haz clic en "Añadir al carrito", luego en "Realizar pedido con LemonSqueezy, y... obtenemos un error bastante básico de `ClientException`.

Anteriormente, utilizamos este truco de `dd($response->getContent(false))` para ver los detalles detrás de `ClientException`. Descomenta esta línea, actualiza la página y... ahora podemos ver el error real.

## Hacer que los mensajes de error sean más informativos

Esto está bien, pero seguro que podríamos mejorarlo aún más. En lugar de utilizar `dd()` para depurar, probemos a envolver el método `client->request()` en otro método. En la parte inferior de esta clase, crea un `private function` llamado `request()`. Esto devolverá un array, y aún tendremos que utilizar los argumentos originales del cliente.

El primer elemento será `string $method`, seguido de `string $url` y una matriz de opciones. Ahora viene la parte divertida: Abre un bloque `try-catch` y, en el `try`, escribe `$response = $this->client->request()` y pasa todas las variables - `$method`, `$url`, y `$options`. Crea una variable `$data` que sea igual a `$response->toArray()`. Vamos a `catch` `ClientException $e` y dentro, haz un poco de magia.

En la parte inferior, `return $data`, y de nuevo en `catch`, queremos el contenido de la respuesta en bruto sin lanzar una excepción, así que diremos `$data = $e->getResponse()->toArray()` y pasaremos `false` como primer argumento. También añadiremos aquí `dd($data)` temporalmente para que podamos ver la respuesta de error de la API. 

A continuación, actualiza el método `createCheckoutUrl()` donde estamos llamando a `request()`. En lugar de `$this->client->request()`, di `$this->request()`, pasando todos los argumentos tal cual. Si nos dirigimos e intentamos comprobar de nuevo... ¡boom! Esto es un volcado correcto de la petición real a la API como una matriz.

## Crear mensajes de error útiles

Bien, en lugar de "volcar y morir", vamos a elaborar algunos mensajes de error que sean más útiles. En nuestro código, busca el método `request()`. Comenta esta sentencia `dd()` y debajo, añade `$mainErrorMessage = 'LS API Error:'`. Ahora, vamos a comprobar si tenemos un error con `$error = $data['errors'][0] ?? null`. Si hay un `error`, entonces haremos más comprobaciones con `if (isset($error['status'])`. Dentro, di `$mainErrorMessage .= ' ' . $error['status']`. Haz lo mismo con `title`, `detail`, y `source.pointer`. Aceleraré esta parte. Por último, `else`, simplemente añadiremos el contenido en bruto a `$mainErrorMessage`. Perfecto

Al final, vamos a `throw` a `new \Exception()` con nuestro nuevo `$mainErrorMessage`. ¡Ya está! Nuestro `$mainErrorMessage` se construye basándose en los campos que tenemos, de lo contrario, simplemente vuelve al contenido en bruto. En ese caso, volveremos a lanzar una excepción con más contexto. ¡Vamos a intentarlo!

En la página de pago, actualiza y... ¡voilá! El mensaje de error genérico es ahora un mensaje personalizado:

> Error de API LS: código de estado 422: Entidad no procesable. el campo {0} debe ser una cadena (en la ruta data.attributes.checkout.data.custom.user_id).

Eso era mucho más fácil de entender. 

Lo único que tenemos que hacer ahora es devolver la tipificación `string` en la línea `user_id`. Ya no necesitamos esta línea `$response->toArray()`, así que podemos eliminarla junto con la `dd()`. Sustituye también la variable `$response` por `$lsCheckout`, puesto que aquí ya tenemos un array de datos del objeto checkout.

Vuelve a actualizar la página para comprobar que funciona, y... ¡ya está! Si hubiera algún error, veríamos aquí nuestro mensaje de error personalizado.

El último paso consiste en sustituir todas las llamadas a `$this->client->request()` restantes por `$this->request()`. Haré esto rápidamente para `retrieveStoreUrl()` y `listOrders()`, y de paso eliminaré las llamadas a `$response->toArray()`.

Si probamos nuestro sitio una vez más... ¡la página de cuenta sigue funcionando... y también la página de pago! Nuestro proceso de tratamiento de errores es ahora eficaz e informativo.

Siguiente paso: Mejoremos la experiencia de nuestros clientes y mostremos la página de pago de LemonSqueezy bajo nuestro dominio utilizando la función de superposición de pago.
