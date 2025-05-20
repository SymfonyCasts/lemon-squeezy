# Implementar el consumidor de webhooks

En el último capítulo, configuramos con éxito el analizador de peticiones de webhook. Este analizador está diseñado para recibir un webhook de LemonSqueezy, verificar su firma, analizar la carga útil y pasar los datos analizados al consumidor del webhook. Ahora que nuestro analizador está listo, podemos abordar la siguiente parte: manejar los datos del webhook en el consumidor.

Empieza abriendo el archivo `LemonSqueezyWebhookConsumer.php` del directorio `src/RemoveEvent/`, y busca el método `consume()`. Podemos deshacernos de este TODO. Nuestra tarea aquí es encontrar el usuario correspondiente al `customer_id` que obtuvimos de los datos del webhook, y conectarlos.

Aquí estamos en una sesión diferente, y eso significa que no podemos acceder al usuario actual directamente desde el servicio `Security`, así que... ¿cómo lo hacemos? Por suerte para nosotros, la documentación de la API "Crear una caja" de LemonSqueezy explica cómo añadir datos personalizados al crear la URL de la caja. Esto es perfecto para pasar nuestro ID de usuario, ¡así que empecemos! Dirígete a `LemonSqueezyApi` en `src/Store/` y busca el método `createCheckoutUrl()`.

Aquí pediremos a los usuarios que inicien sesión antes de poder realizar el pago. Esto es crucial porque es la información que necesitamos para enlazar con el cliente LemonSqueezy correspondiente. Podemos hacerlo en la firma del método. El requisito de inicio de sesión significa que ya no necesitamos la declaración `if (user)` que aparece a continuación, así que podemos eliminarla y ordenar estas líneas. A continuación, añade `$attributes['checkout_data]['custom']['user_id'] = $user->getId()`. Este campo `custom` nos permite pasar cualquier dato personalizado que necesitemos a LemonSqueezy.

El objetivo es compartir el ID de usuario con LemonSqueezy cuando un cliente realiza un pedido. Si volvemos a `OrderController`, te darás cuenta de que PhpStorm no está muy contento con esta llamada a `createCheckoutUrl()`. Eso es porque todavía tenemos que hacer que sea un requisito en la firma de ese método. Elimina este `?` y ya está - no hace falta llamar a `denyAccessUnlessGranted()`.

Para confirmar que todo funciona como esperamos, en nuestro sitio, cierra la sesión... añade un producto al carrito e intenta pasar por caja. Como somos un usuario no autenticado, deberíamos ser redirigidos a la página de inicio de sesión, y... ¡perfecto!

## Manejo del consumidor Webhook

De vuelta al código, en nuestro método `consume()`, pon `$payload = $event->getPayload()`. Debajo, pon `$userId = $payload['meta']['custom_data']['user_id'] ?? null`. También podemos dejar aquí un comentario mencionando que `getUser()` no funcionará en webhooks como usuario no autenticado en este proceso.

Ahora, vamos a realizar una comprobación de sanidad con `if (!$userId)`. Si esta comprobación falla, vamos a `throw new InvalidArgumentException()` con un `sprintf()` dentro diciendo `'User ID not found in LemonSqueezy webhook: %s', $userId`.

Para acceder a `EntityManager`, en nuestro constructor, inyecta `private EntityManagerInterface $entityManager`.

De vuelta al método `consume()`, continúa con `$user = $this->entityManager->getRepository(User::class)->find($userId)`. 

A continuación, si `$user` no existe, haremos `throw new EntityNotFoundException()` (elige el de `Doctrine\ORM`). También añadiremos `sprintf()` como argumento, indicaremos `User "%s" not found for LemonSqueezy webhook "%s"!`, y pasaremos `$userId` y `$event->getId()`. 

Debajo, añadiremos `match ($event->getName())`, y para `order_created`, llamaremos a `$this->handleOrderCreatedEvent()`. Este método aún no existe, pero lo crearemos más adelante. Pasa también `$event` y `$user` como argumentos. Llegados a este punto, sólo deberíamos tener eventos compatibles, pero por si acaso nos falta algo, añade un `default` que `throw new LogicException()`, con `sprintf('Unsupported LemonSqueezy event: %s', $event->getName())`. Muy bien.

## Crear el evento ManejarOrden

Antes de que se nos olvide, volvamos atrás y creemos el `handleOrderCreatedEvent()`. Éste será un `private function`, y parece que PhpStorm añadió un argumento - `RemoteEvent $event` - pero olvidó el segundo, así que añadiremos `User $user` manualmente.

Dentro, vamos a buscar la carga útil con `$payload = $event->getPayload()`. A continuación, obtén el ID de cliente del payload: `$customerId = $payload['data']['attributes']['customer_id']`. Si te preguntas de dónde viene esto, puedes encontrar esta ruta en la carga útil de la petición Ngrok.

Bien, ya tenemos el `customer_id`, pero aún necesitamos una nueva propiedad en el `User` para guardarlo. En tu terminal, crea una nueva pestaña y ejecuta:

```terminal
bin/console make:entity
```

Para el nombre de la clase, escribiremos `User`. Para el nombre de la propiedad, llámalo `lsCustomerId`. Que sea una cadena, con una longitud de 255, y que sea anulable. Pulsa `Enter` una vez más y... ¡listo!

De vuelta a nuestro código, abre `src/Entity/User.php`... y si nos desplazamos hacia abajo... ¡aquí está nuestra nueva columna! Pongámosla también en `unique: true`. Tiene muy buena pinta, y si nos desplazamos hacia abajo, veremos que también ha creado un getter y un setter para este campo. ¡Genial! 

Ahora tenemos que crear una migración. Podemos hacerlo con:

```terminal
bin/console make:migration
```

Si vamos a comprobarlo... ¡tiene buena pinta! Simplemente añadiremos una descripción rápida - `Add customer ID property to User entity` - y de vuelta a nuestro terminal, migraremos con:

```terminal
bin/console doctrine:migration:migrate
```

Una vez terminado, volvemos a la `handleOrderCreatedEvent()`, y la establecemos en nuestra nueva columna con `$user->setLsCustomerId()`. Asegúrate de pasar la variable `$customerId` como argumento. Para guardarla, llama a `$this->entityManager->flush()`.

## Probar el webhook

¡Es hora de volver a probar el webhook! Soy un fan del inspector Ngrok, así que lo utilizaré. Hmm... un error. El webhook no tiene un `user_id` establecido en datos personalizados para ese caso concreto. Eso tiene sentido. Con Ngrok, podemos modificar el contenido original del webhook y reproducirlo con las modificaciones. Pero para estar más seguros de que se tienen en cuenta nuestros cambios, volveremos a pasar por el proceso de pago para que LemonSqueezy pueda establecer el `user_id` correctamente.

Iniciemos sesión de nuevo, añadamos un producto al carrito e intentemos pagar. Uy, otro error. Volvamos a nuestro código y descomentemos este `dd()` para tener una mejor idea de lo que está ocurriendo aquí. Si actualizamos nuestro sitio... ¡ah! Parece que el campo debe ser una cadena, y está apuntando al `user_id` personalizado que hemos añadido. No puedes pasar cualquier cosa, así que pasar todo el objeto usuario definitivamente no funcionará. Vuelve a nuestro código y comenta de nuevo ese `dd()`... luego, aquí arriba... especifiquemos que este ID de usuario es un `string` e intentémoslo de nuevo. Cuando actualicemos... ¡éxito! ¡Estamos en la página de pago!

Rellenemos los datos de la tarjeta... la dirección... hagamos el pago, y esperemos al webhook. ¡Sí! Nuestra transacción ha sido aceptada y, aquí, tenemos un código de estado 202.

Si miramos la petición, podemos ver nuestro `custom_data` donde el `user_id` es igual a `1`. También podemos comprobar la base de datos con un práctico comando SQL. En tu terminal, ejecuta `bin/console doctrine:query:sql` y, entre comillas dobles, introduce `SELECT * FROM user WHERE id = 1`:

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user WHERE id = 1"
```

Ya que tenemos un ID de "1" para el usuario actual. Pulsa "intro" y... ¡sí! El `lsCustomerId` queda configurado con este ID único. Esto es lo que necesitamos para crear una lista de pedidos realizados por este cliente.

Pero antes de hacerlo, veamos cómo podemos probar nuestro webhook. Eso a continuación.
