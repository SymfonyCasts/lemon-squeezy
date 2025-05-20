# Implementación del analizador de peticiones Webhook

En el último capítulo, vimos cómo LemonSqueezy enviaba su primer webhook a nuestro sitio web local a través del túnel Ngrok. Pero no dio en el clavo, porque aún tenemos que configurar el manejo real del webhook. Así que, ¡manos a la obra!

Cuando ejecutamos el comando `make:webhook`, generó dos archivos: un analizador de peticiones y un consumidor. Empecemos por el analizador. Abre`src/Webhook/LemonSqueezyRequestParser.php`.

Es importante recordar que todos los webhooks de LemonSqueezy se envían como una petición POST con una carga JSON a la ruta `/webhook/lemon-squeezy` que configuramos en el panel de control. Teniendo esto en cuenta, tenemos que modificar el método `getRequestMatcher()`.

Dentro de `getRequestMatcher()`, tenemos `ChainRequestMatcher`. Esto incluye básicamente los otros tres comparadores. Establece el `PathRequestMatcher` en`/webhook/lemon-squeezy`. Luego tenemos que confirmar que se trata de una petición a `POST`. Para que quede más claro, utiliza la constante `Request::METHOD_POST`, que no es más que una forma elegante de decir "POST". Este `IsJsonRequestMatcher` está bien tal cual y no necesita argumentos.

[[[ code('e66723714b') ]]]

Bien, ya estamos listos para pasar al siguiente método: `doParse()`. Lo primero es verificar la firma del webhook. Para mantener las cosas ordenadas, crearemos un método separado para esto. Añade `private function verifySignature()`... y esta función recibirá dos argumentos: `Request $request` y`string $secret`. El tipo de retorno es `void`.

[[[ code('e490c9c895') ]]]

A continuación, tenemos que calcular el hash de la carga útil de la petición utilizando el algoritmo de LemonSqueezy: `$payload = $request->getContent()`. Empezaremos con una variable hash: `$hash = hash_hmac('sha256', $payload, $secret)`. Luego tenemos que obtener la firma de la cabecera de la petición con`$signature = $request->headers->get('X-Signature', '')`.

[[[ code('fd27cc281e') ]]]

Ahora es el momento de ver si el hash coincide con la firma. Si lo hace (`if (hash_equals($hash, $signature))`), entonces estamos listos. Si no,`throw new RejectWebhookException()` con un código de estado `Response::HTTP_UNAUTHORIZED`- un 401. También añadiremos un mensaje - `Invalid LemonSqueezy signature!` - para saber qué ha pasado.

Una vez hecho esto, podemos llamar a esto desde el método `doParse()` del principio. Despejaré y eliminaré este código marcador de posición. Ahora verifica la firma con `$this->verifySignature($request, $secret)`. Elimina el resto de este código marcador de posición y obtén la carga útil de la petición:`$payload = $request->toArray()`. A continuación, escribe`$eventName = $payload['meta']['event_name']` y`$webhookId = $payload['meta']['webhook_id']`.

[[[ code('82fa32dd43') ]]]

También añadiremos una comprobación de sanidad para confirmar la presencia de `$eventName` y`webhookId` con `if (!$eventName || !$webhookId) {`. Dentro,`throw new RejectWebhookException()`, dale un código de estado `Response::HTTP_BAD_REQUEST`y un mensaje de error:`Request payload does not contain required fields.`.

Bien, comprobemos si es un evento admitido. De momento, sólo rastreamos `order_created`. Si no lo es, lanzaremos otra excepción -`RejectWebhookException()` - con el estado `Response::HTTP_BAD_REQUEST`, y utilizaremos`sprintf('Unsupported event type: %s', $eventName)`.

Para juntarlo todo, devuelve un`new RemoteEvent($eventName, $webhookId, $payload)`. Ya está ¡Nuestro analizador sintáctico debería estar listo!

[[[ code('7e45194fc1') ]]]

Si lo hemos hecho correctamente, LemonSqueezy debería recibir un código de estado 200 correcto. Podemos comprobarlo reenviando el webhook desde el panel de control de LemonSqueezy o reproduciendo el webhook desde la interfaz web de Ngrok. Haré clic en "Reproducir" y... ¡es un código de estado 202! ¡Sigue contando! ¡Éxito!

Nuestro analizador funciona y ¡es hora de celebrarlo! A continuación, vamos a implementar el consumidor del webhook.
