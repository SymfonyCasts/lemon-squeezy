# Implementar el consumidor de webhooks

En el último capítulo, configuramos con éxito el analizador de peticiones de webhook. Este analizador está diseñado para recibir un webhook de LemonSqueezy, verificar su firma, analizar la carga útil y pasar los datos analizados a un consumidor de webhook. Ahora que nuestro analizador está listo, podemos abordar la siguiente parte: manejar los datos del webhook en el consumidor.

Empieza abriendo el archivo `LemonSqueezyWebhookConsumer.php` del directorio`src/RemoveEvent/`, y busca el método `consume()`. Podemos deshacernos de este TODO. Nuestra tarea aquí es encontrar el usuario correspondiente al `customer_id`que obtenemos de los datos del webhook, y conectarlos.

Una cosa a tener en cuenta es que el consumidor es activado por una petición totalmente independiente: el webhook de LemonSqueezy. No sólo eso, sino que los consumidores son gestionados por Symfony Messenger. Es posible que sean procesados por un trabajador en segundo plano. La cuestión es que no tenemos acceso al objeto usuario original como en el controlador de pago.

Entonces... ¿cómo obtenemos el usuario en el consumidor? Por suerte para nosotros, la documentación de la API "Crear una compra" de LemonSqueezy explica cómo añadir datos personalizados al crear la URL de la compra. Esto es perfecto para pasar nuestro ID de usuario, ¡así que empecemos! Dirígete a `LemonSqueezyApi` en`src/Store/` y busca el método `createCheckoutUrl()`.

Aquí, tenemos que hacer que el usuario no sea opcional. Esto es crucial porque es la información que necesitamos para enlazar con el cliente LemonSqueezy correspondiente. Elimina el `?` de la sugerencia de tipo `User` y la declaración `if ($user)` que ahora es innecesaria. Ahora, añade`$attributes['checkout_data']['custom']['user_id'] = $user->getId()`. Este campo`custom` nos permite pasar cualquier dato personalizado que necesitemos a LemonSqueezy y se pondrá a nuestra disposición en la carga útil del webhook.

El objetivo es compartir el ID de usuario con LemonSqueezy cuando un cliente realiza un pedido. Para ello, es necesario que el usuario haya iniciado sesión. Volviendo a`OrderController::checkout()`, todo lo que hay que hacer para que esta ruta requiera autenticación es eliminar el `?` de la sugerencia de tipo `User`. Muy bonito, ¿verdad?

Para confirmar que todo funciona según lo esperado, en nuestro sitio, cierra la sesión... añade un producto al carrito e intenta pasar por caja. Como somos un usuario no autenticado, deberíamos ser redirigidos a la página de inicio de sesión, y... ¡perfecto!

## Manejo del consumidor Webhook

De vuelta en el método `LemonSqueezyWebhookConsumer::consume()`, establece`$payload = $event->getPayload()`.

Para acceder al `user_id` que establecimos en `createCheckoutUrl()`, escribe`$userId = $payload['meta']['custom_data']['user_id'] ?? null`. Añadiré un comentario rápido para explicar que no podemos obtener el usuario por "medios tradicionales".

Ahora, escribe una comprobación de cordura con `if (!$userId)`. Si esta comprobación falla, escribiremos `throw new InvalidArgumentException()` con`sprintf('User ID not found in LemonSqueezy webhook: %s', $event->getId())`.

Ahora tenemos el `$userId`, pero ¿cómo obtenemos el objeto usuario? En nuestro constructor, inyecta `private EntityManagerInterface $entityManager`.

De vuelta al método `consume()`, recupera el usuario con`$user = $this->entityManager->getRepository(User::class)->find($userId)`.

A continuación, si `$user` no existe, escribiremos `throw new EntityNotFoundException()`(elige el de `Doctrine\ORM`). Para el mensaje escribe`sprintf('User "%s" not found for LemonSqueezy webhook "%s"!', $userId, $event->getId())`.

A continuación, añade `match ($event->getName())`, y para `order_created`, llama a`$this->handleOrderCreatedEvent()`. Este método aún no existe, pero lo crearemos más adelante. Pasa también `$event` y `$user` como argumentos. Llegados a este punto, sólo deberíamos tener eventos admitidos, pero por si acaso nos falta algo, añade un `default` que `throw new LogicException()`, con`sprintf('Unsupported LemonSqueezy event: %s', $event->getId())`. Muy bien.

## Crear el evento ManejarOrden

Ahora, crea el método `handleOrderCreatedEvent()` con PhpStorm como una función privada. Parece que PhpStorm añadió un argumento - `RemoteEvent $event` - pero olvidó el segundo, así que añade`User $user` manualmente y devuelve el tipo: `void`.

Dentro, obtén la carga útil con `$payload = $event->getPayload()`. Debajo, busca el ID del cliente con`$customerId = $payload['data']['attributes']['customer_id']`. Si te preguntas de dónde ha salido esto, puedes encontrarlo en el inspector de Ngrok.

Vale, ya tenemos el `customer_id`, pero aún necesitamos una nueva propiedad en el`User` para guardarlo. En tu terminal, crea una nueva pestaña y ejecuta:

```terminal
bin/console make:entity
```

Para la clase, escribe `User` para coger la entidad existente. Para el nombre de la propiedad, llámala`lsCustomerId`. Haz que sea una cadena con una longitud de 255, y anulable. Pulsa `Enter` una vez más y... ¡listo!

De vuelta a nuestro código, abre `src/Entity/User.php`... si nos desplazamos hacia abajo... ¡aquí está nuestra nueva columna! Establécela en `unique: true`. Esto tiene muy buena pinta, y si nos desplazamos hacia abajo, podemos ver que también ha creado un getter y un setter para el campo. ¡Genial!

Ahora tenemos que crear una migración. Hazlo con:

```terminal
bin/console make:migration
```

Si vamos a comprobarlo... ¡tiene buena pinta! Simplemente añadiremos una descripción rápida -`Add customer ID property to User entity` - y de vuelta a nuestro terminal, migra con:

```terminal
bin/console doctrine:migration:migrate
```

Una vez terminado, vuelve a `handleOrderCreatedEvent()` y llama a nuestro nuevo configurador: `$user->setLsCustomerId()` con `$customerId`. Para guardarlo, llama a`$this->entityManager->flush()`.

## Probar el webhook

¡Es hora de volver a probar el webhook! En el inspector Ngrok, vuelve a reproducir. Hmm... un error. Es un poco difícil de ver, pero aquí está:

> ID de usuario no encontrado en el webhook LemonSqueezy

Esto tiene sentido: cuando este webhook se ejecutó originalmente, no tenía configurado el `user_id`. Con Ngrok, podemos modificar el contenido del webhook original y reproducirlo con modificaciones, pero... para estar más seguros de que se tienen en cuenta nuestros cambios, volveremos a pasar por el proceso de compra para que LemonSqueezy pueda configurar el `user_id` correctamente.

Vuelve a iniciar sesión, añade un producto al carrito e intenta pasar por caja. Uy, otro error: un 422. Salta a `LemonSqueezyApi` y descomenta este `dd()` para ver qué está pasando aquí. Si actualizamos nuestro sitio... ¡ah!

> ...el campo debe ser una cadena...

y está apuntando al `user_id` personalizado que añadimos... Resulta que LemonSqueezy es bastante estricto con los tipos. Vuelve a nuestro código, comenta de nuevo ese `dd()`... y, aquí arriba... convierte este `$user->getId()` en una cadena. Volvemos a nuestra aplicación... refrescamos... ¡y éxito! ¡Estamos en la página de pago!

Rellenemos los datos de la tarjeta... la dirección... realicemos el pago, y esperemos al webhook. ¡Sí! Nuestra transacción ha sido aceptada y, aquí, tenemos un código de estado 202.

Si miramos la petición, podemos ver que nuestra `custom_data` -> `user_id`es igual a `1`. También podemos comprobar la base de datos con un práctico comando SQL. En tu terminal, ejecuta:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user WHERE id = 1"
```

Este `lsCustomerId` es el ID único de LemonSqueezy. ¡Genial!

Antes de continuar, escribamos algunas pruebas para nuestra configuración de webhook. ¡Eso a continuación!
