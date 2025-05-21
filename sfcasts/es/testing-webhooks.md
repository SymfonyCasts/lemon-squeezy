# Escribir una prueba de integración para webhooks

Anteriormente, configuramos un webhook que guarda el ID de cliente del usuario que realizó un pedido. Los webhooks son a la vez increíblemente útiles y ligeramente aterradores. Son un componente clave en muchas aplicaciones web contemporáneas, especialmente en el comercio electrónico. Si fallan, podrían significar la pérdida de ventas o la rotura de funciones: malas noticias para las empresas grandes y pequeñas. Por eso es tan importante probarlas. 

Como desarrolladores, no somos grandes aficionados a las pruebas manuales: preferimos la automatización. Si eres nuevo en las pruebas en Symfony o PHP, ¡no temas! Tenemos un montón de cursos relacionados con las pruebas que te guiarán a través de todo, desde las pruebas unitarias básicas hasta las pruebas de navegador completas. Para más información, echa un vistazo a nuestro [Tema de pruebas](https://symfonycasts.com/tracks/testing) en SymfonyCasts. Volvamos a nuestra prueba de webhook

En primer lugar, tenemos que instalar las herramientas de prueba de Symfony. En tu terminal, ejecuta:

```terminal
composer require test --dev
```

Esto nos da acceso a PHPUnit y a un montón de herramientas útiles. Lo ideal sería probar el analizador de peticiones y el consumidor con PHPUnit, pero como no nos centraremos en las pruebas en este curso, considéralo una tarea divertida.

***SEEALSO
Si eres nuevo en PHPUnit, echa un vistazo a nuestro [curso de introducción a PHPUnit](https://symfonycasts.com/screencast/phpunit) en SymfonyCasts.
***

Por ahora, vamos a sumergirnos en algo un poco más complejo y probar la integración del webhook. Nuestra tarea consiste en escribir una prueba de integración completa para el webhook `order_created` que implementamos anteriormente. 

## Generar una nueva prueba

Empezaremos generando una nueva prueba. Aquí es donde brilla el MakerBundle. En tu terminal, ejecuta:

```terminal
bin/console make:test
```

Selecciona `WebTestCase`... y llamaremos a nuestra prueba `Webhook\LemonSqueezyRequestParser`. MakerBundle nos proporcionará un archivo con código repetitivo. Podemos encontrarlo en `tests/Webhook/LemonSqueezyRequestParserTest.php`. Cambia el nombre del método de prueba por defecto por algo más descriptivo, como `testOrderCreatedWebhook()`. Por ahora mantendremos la línea que verifica que la respuesta es correcta, pero modificaremos el mensaje de error para que sea `Webhook failed!`. Vamos a probarlo En el terminal, ejecuta:

```terminal
bin/phpunit
```

Obtenemos un error, pero es lo esperado.

> No existe tal tabla en el entorno de prueba. 

## Corregir el error

Nuestra prueba está fallando porque necesitamos configurar una base de datos de prueba. Podríamos hacerlo manualmente mediante comandos de la consola de Doctrine, pero vamos a aprovechar [Zenstruck Foundry](https://github.com/zenstruck/foundry), que ya hemos instalado, para restablecer y gestionar automáticamente la base de datos de prueba.

En la clase de prueba, añade `use ResetDatabase`. Esto también limpia la base de datos entre pruebas, de modo que no tengamos que preocuparnos por errores de correos duplicados. ¡Genial! Si volvemos a ejecutar la prueba... ¡esta vez ha pasado! ¡Estupendo! Ahora, escribamos una prueba real.

## Crear datos ficticios

Para ello, necesitamos datos ficticios. ¡Foundry también puede ayudarte con eso! La llamada a `static::createClient()` arranca el núcleo de Symfony, así que es seguro utilizar `UserFactory` justo debajo. Podemos crear un nuevo usuario de prueba diciendo `$user = UserFactory::new()->create()` y pasando `[ 'email' => 'test@example.com', 'plainPassword' => 'testpass', 'firstName' => 'Test', ]`.

Ahora que tenemos un usuario, tenemos que simular una petición real `POST` a la ruta del webhook. Podemos hacerlo con `$client->request('POST', '/webhook/lemon-squeezy', [], [], [], $json)`.

Podemos copiar la carga útil JSON de la interfaz web de Ngrok (si aún la tienes en funcionamiento), o podemos copiarla del panel de control de LemonSqueezy en "Webhooks". Copia todo el bloque de petición y, de vuelta a nuestro código, en `tests/`, crea un nuevo directorio. Llámalo `fixtures`, y dentro de él, crea un nuevo archivo. Llámalo `order_created.json`, y... pega.

En nuestra prueba, encima de la petición, pon `$json = file_get_contents(__DIR__.'/../fixtures/order_created.json')`. Al final, añade otra afirmación - `$this->assertNotNull()` - y... ¡huy! Me olvidé de crear una variable `$user` más arriba, así que vamos a arreglarlo. Ahora, pasa `$user->getLsCustomerId()` como argumento a nuestra nueva llamada de aserción, y para el mensaje de error, di `LemonSqueezy customer ID not set!`. Por último, añade `$this->assertEquals(1000001, $user->getLsCustomerId(), 'LemonSqueezy customer ID mismatch!')`. ¡Uf! ¡Hora de probar!

En tu terminal, ejecuta de nuevo la prueba:

```terminal-silent
bin/phpunit
```

¡Un error! Si te desplazas un poco hacia arriba, verás el mensaje de excepción:

> ¡Firma LemonSqueezy no válida!

¡Vaya! Bueno, era de esperar. Hemos copiado y pegado la cadena de carga útil, pero parece que es un poco diferente de la original, por lo que su firma ya no es válida.

Si echamos un vistazo al analizador sintáctico de la petición... sip. Añadimos este método `verifySignature()` para evitar peticiones no autorizadas a la ruta, pero ahora somos nosotros los que intentamos enviar una petición falsa allí. ¡Cómo han cambiado las tornas!

Podríamos saltarnos completamente la comprobación de la firma aquí inyectando el entorno Symfony, añadiendo `if ($this->env === 'test')`, y simplemente devolviendo. Pero no me gusta esta solución. Una solución mejor sería firmar las peticiones en tu prueba para que parezcan legítimas.

Copia esta línea de hash, pégala antes de la llamada a la petición, y para la carga útil, utiliza nuestra variable `$json`. Para el secreto, utilicemos `$_ENV['LEMON_SQUEEZY_SIGNING_SECRET']`. A continuación, pasa este `$hash` al quinto argumento del método `request()`. Dentro de la matriz, añade `['HTTP_X-Signature' => $hash]`, y Symfony lo convertirá en una cabecera.

Ejecuta de nuevo la prueba, y... otro error, pero este es diferente. ¡Es una buena señal! La carga útil que hemos utilizado contiene un ID de usuario y un ID de cliente que deberían ser dinámicos y coincidir con nuestros datos de prueba dinámicos. Actualicemos nuestro archivo `order_created.json` con algunos marcadores de posición. Para el valor `user_id`, utiliza `%user_id%`, y para el valor `customer_id`, utiliza `%customer_id%`. Por último, sustituye los marcadores de posición en nuestra prueba procesando de nuevo la variable `$json` con `$json = strtr($json, [])`, y pasando la matriz como `['%user_id%' => $user->getId(), '%customer_id%' => 1000001]`.

Ejecuta de nuevo la prueba y esta vez... ¡pasó!

¡Enhorabuena! Acabas de completar una prueba de integración completa para un webhook. Has creado un usuario, has simulado una petición real de webhook y has verificado que tu aplicación lo gestiona todo correctamente. ¡Buen trabajo!

A continuación, utilizaremos nuestra nueva relación cliente en la entidad usuario y mostraremos los pedidos de los usuarios en la sección cuenta.
