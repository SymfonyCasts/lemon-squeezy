# Implementar el consumidor de webhooks

En el último capítulo, configuramos con éxito el analizador de peticiones de webhook. Este analizador está diseñado para recibir un webhook de LemonSqueezy, verificar su firma, analizar la carga útil y pasar los datos analizados a un consumidor de webhook. Ahora que nuestro analizador está listo, podemos abordar la siguiente parte: manejar los datos del webhook en el consumidor.

Empieza abriendo el archivo `LemonSqueezyWebhookConsumer.php` del directorio`src/RemoveEvent/`, y busca el método `consume()`. Podemos deshacernos de este TODO. Nuestra tarea aquí es encontrar el usuario correspondiente al `customer_id`que obtenemos de los datos del webhook, y conectarlos.

Aquí estamos en una sesión diferente, y eso significa que no podemos acceder al usuario actual directamente desde el servicio `Security`, así que... ¿cómo obtenemos el usuario? Por suerte para nosotros, la documentación de la API "Crear una caja" de LemonSqueezy explica cómo añadir datos personalizados al crear la URL de la caja. Esto es perfecto para pasar nuestro ID de usuario, ¡así que empecemos! Dirígete a `LemonSqueezyApi` en`src/Store/` y busca el método `createCheckoutUrl()`.

Aquí pediremos a los usuarios que inicien sesión antes de poder realizar el pago. Esto es crucial porque es la información que necesitamos para enlazar con el cliente LemonSqueezy correspondiente. Podemos hacerlo en la firma del método. El requisito de inicio de sesión significa que ya no necesitamos la declaración `if ($user)` que aparece a continuación, así que podemos eliminarla y ordenar estas líneas. A continuación, añade`$attributes['checkout_data']['custom']['user_id'] = $user->getId()`. Este campo`custom` nos permite pasar cualquier dato personalizado que necesitemos a LemonSqueezy y estará a nuestra disposición en la carga útil del webhook.

El objetivo es compartir el ID de usuario con LemonSqueezy cuando un cliente realiza un pedido. Si volvemos a `OrderController`, verás que PhpStorm no está muy contento con esta llamada a `createCheckoutUrl()`. Eso es porque `User`ya no es opcional. Elimina este `?` y ya está.

Para confirmar que todo funciona según lo esperado, en nuestro sitio, cierra la sesión... añade un producto al carrito e intenta pasar por caja. Como somos un usuario no autenticado, deberíamos ser redirigidos a la página de inicio de sesión, y... ¡perfecto!

## Manejo del consumidor Webhook

De vuelta al código, en nuestro método `consume()`, pon`$payload = $event->getPayload()`. Debajo, escribe`$userId = $payload['meta']['custom_data']['user_id'] ?? null`.

Ahora, hagamos una comprobación de cordura con `if (!$userId)`. Si esta comprobación falla, escribiremos `throw new InvalidArgumentException()` con un `sprintf()` dentro diciendo`'User ID not found in LemonSqueezy webhook: %s', $userId`.

Para acceder a `EntityManager`, en nuestro constructor, inyecta`private EntityManagerInterface $entityManager`.

De vuelta al método `consume()`, continúa con`$user = $this->entityManager->getRepository(User::class)->find($userId)`.

A continuación, si `$user` no existe, haremos `throw new EntityNotFoundException()`(elige el de `Doctrine\ORM`). También añadiremos `sprintf()` como argumento, indicaremos `User "%s" not found for LemonSqueezy webhook "%s"!`, y pasaremos `$userId`y `$event->getId()`.

Debajo, añadiremos `match ($event->getName())`, y para `order_created`, llamaremos a`$this->handleOrderCreatedEvent()`. Este método aún no existe, pero lo crearemos más adelante. Pasa también `$event` y `$user` como argumentos. Llegados a este punto, sólo deberíamos tener eventos compatibles, pero por si acaso nos falta algo, añade un `default` que `throw new LogicException()`, con`sprintf('Unsupported LemonSqueezy event: %s', $event->getName())`. Muy bien.

## Crear el evento ManejarOrden

Antes de que se nos olvide, volvamos atrás y creemos el `handleOrderCreatedEvent()`. Éste será un `private function`, y parece que PhpStorm añadió un argumento - `RemoteEvent $event` - pero olvidó el segundo, así que añadiremos`User $user` manualmente.

Dentro, vamos a buscar la carga útil con `$payload = $event->getPayload()`. A continuación, busca el ID de cliente con`$customerId = $payload['data']['attributes']['customer_id']`. Si te preguntas de dónde viene esto, puedes encontrar esta ruta en la carga útil de la petición Ngrok.

Vale, ya tenemos el `customer_id`, pero aún necesitamos una nueva propiedad en el`User` para guardarlo. En tu terminal, crea una nueva pestaña y ejecuta:

```terminal
bin/console make:entity
```

Para el nombre de la clase, escribiremos `User`. Para el nombre de la propiedad, llámalo`lsCustomerId`. Haz que sea una cadena con una longitud de 255, y anulable. Pulsa `Enter` una vez más y... ¡listo!

De vuelta a nuestro código, abre `src/Entity/User.php`... si nos desplazamos hacia abajo... ¡aquí está nuestra nueva columna! Pongámosla también en `unique: true`. Tiene muy buena pinta, y si nos desplazamos hacia abajo, veremos que también ha creado un getter y un setter para el campo. ¡Estupendo!

Ahora tenemos que crear una migración. Podemos hacerlo con:

```terminal
bin/console make:migration
```

Si vamos a comprobarlo... ¡tiene buena pinta! Simplemente añadiremos una descripción rápida -`Add customer ID property to User entity` - y de vuelta a nuestro terminal, migraremos con:

```terminal
bin/console doctrine:migration:migrate
```

Una vez terminado, vuelve a `handleOrderCreatedEvent()` y llama a nuestro nuevo configurador: `$user->setLsCustomerId()` con `$customerId`. Para guardarlo, llama a`$this->entityManager->flush()`.

## Probar el webhook

¡Es hora de volver a probar el webhook! En el inspector Ngrok, vuelve a reproducir. Hmm... un error. Es un poco difícil de ver, pero aquí está:

> ID de usuario no encontrado en el webhook LemonSqueezy

Esto tiene sentido: cuando este webhook se ejecutó originalmente, no tenía configurado el `user_id`. Con Ngrok, podemos modificar el contenido del webhook original y reproducirlo con modificaciones, pero... para estar más seguros de que se tienen en cuenta nuestros cambios, volveremos a pasar por el proceso de pago para que LemonSqueezy pueda configurar el `user_id` correctamente.

Iniciemos sesión de nuevo, añadamos un producto al carrito e intentemos pagar. Uy, otro error: un 422. Salta a `LemonSqueezyApi` y descomenta este `dd()` para ver qué está pasando aquí. Si actualizamos nuestro sitio... ¡ah!

> ...el campo debe ser una cadena...

y está apuntando al `user_id` personalizado que añadimos... Vuelve a nuestro código, comenta de nuevo ese `dd()`... y luego, aquí arriba... convierte este `$user->getId()` en una cadena. Volvemos a nuestra aplicación... refrescamos... ¡y éxito! ¡Estamos en la página de pago!

Rellenamos los datos de la tarjeta... la dirección... realizamos el pago, y esperamos al webhook. ¡Sí! Nuestra transacción ha sido aceptada y, aquí, tenemos un código de estado 202.

Si miramos la petición, podemos ver que nuestro `custom_data` y `user_id`son iguales a `1`. También podemos comprobar la base de datos con un práctico comando SQL. En tu terminal, ejecuta:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user WHERE id = 1"
```

Este `lsCustomerId` es el ID único de LemonSqueezy. ¡Genial!

Antes de continuar, escribamos algunas pruebas para nuestra configuración de webhook. ¡Eso a continuación!
