# Mejorando la seguridad de los eventos Javascript

Ya hemos demostrado que sincronizamos el ID de cliente de LemonSqueezy con el usuario de nuestra base de datos utilizando dos métodos diferentes: webhooks, con los que conseguimos una configuración de producción bastante sólida, y mediante eventos JavaScript de LemonSqueezy, que nos ayudan a saltarnos la configuración de Ngrok y webhook localmente. Es perfectamente aceptable utilizar ambos métodos simultáneamente.

Pero, dediquemos un momento a examinar nuestra acción `handleCheckout()`. Puede que tengamos un posible problema de seguridad entre manos. Los usuarios malintencionados podrían intentar enviar una petición AJAX a esta ruta utilizando un ID de cliente de LemonSqueezy diferente. Esto podría anular su propio ID de cliente, lo que podría llevar a una situación en la que nuestra aplicación generara una URL firmada para ese cliente y se la entregara al atacante. Esto les permitiría ver información personal, hacer cambios en nombre del cliente e incluso realizar compras fraudulentas.

[[[ code('45ff64f11f') ]]]

Pero, ¡no te preocupes! ¡Tenemos algunas soluciones! Podríamos utilizar la sincronización de clientes a través de los eventos JavaScript sólo en modo dev. Esto significa que no funcionará en producción, pero sí localmente. Los usuarios reales sólo se sincronizarían a través de webhooks con una firma firmada en producción. Sin embargo, ¡creo que podemos hacerlo mejor!

¿Recuerdas cuando estábamos configurando el consumidor de webhooks? Allí no podíamos acceder al usuario actual, así que añadimos el ID de usuario como datos de comprobación personalizados a los que luego podíamos acceder desde la carga útil del webhook. Estos datos personalizados también están disponibles en los datos del evento "Checkout.Success"

Así, podemos enviar ese ID de usuario a nuestra acción `handleCheckout()` y verificar que el ID de usuario actual coincide con el ID de usuario de los datos personalizados.

## Añadir comprobaciones adicionales para evitar la anulación de datos

Abre `lemons-squeezy_controller.js`. En `LemonSqueezy.Setup()`, descomenta la línea `console.log(data)` para depurar la respuesta y encontrar la estructura de la ruta para el ID de usuario. O, si quieres saltarte esa parte, puedes confiar en mí y escribir `const userId = data.data.order.meta.custom_data.user_id`.

A continuación, pasa esta variable `userId` como primer argumento para`#handleCheckout()`. En `#handleCheckout()`, cambia la firma a`userId, lsCustomerId` y, aquí abajo, pasa `userId` a`URLSearchParams()`, igual que hicimos con `lsCustomerId`.

[[[ code('eaf7378c76') ]]]

De vuelta a `OrderController.php`, en la parte superior, crea una variable `$userId`y hazla igual a `$request->request->get('userId')`.

Debajo, añade: `if ($userId !== $user->getId())`. Como el método`getId()` devuelve un número entero, y como me encanta la comparación estricta, haz un typecast de esto a `string`.

Si se cumple esta condición, `throw $this->createAccessDeniedException()`. Dentro, escribe `sprintf()`, con:

> ¡El ID de usuario actual "%s" no coincide con el ID de usuario "%s" del pedido!

Y pasa `$user->getId()` y `$userId` como argumentos.

[[[ code('16ac40a890') ]]]

Ahora podemos establecer con seguridad el ID del cliente, ya que estamos seguros de que está relacionado con el usuario actual.

## Probar nuestra configuración

Dirígete a tu terminal y ejecuta:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user"
```

Tenemos el `lsCustomerId` configurado, así que vamos a restablecerlo a `NULL` con:

```terminal
bin/console doctrine:query:sql "UPDATE user SET lsCustomerId=NULL WHERE id=1"
```

¡Vamos a probarlo! En la página del carrito, haz clic en el botón "Pagar", rellena los datos de facturación... y haz clic en "Pagar". Si esperamos un momento... veremos el mensaje "Gracias por su pedido". Hasta aquí, ¡todo bien!

De vuelta al terminal, ejecuta de nuevo la consulta `SELECT`:

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user"
```

Aquí podemos ver que el campo `lsCustomerId` vuelve a estar configurado. En este momento no estamos ejecutando túneles Ngrok, por lo que se estableció a través del evento JavaScript. ¡Funciona!

## Utiliza siempre HTTPS

¡Ahí lo tienes! Hemos visto cómo LemonSqueezy gestiona las compras. Las credenciales del carrito nunca se envían a nuestro servidor, sino que se envían directamente al servidor de LemonSqueezy a través del iFrame que hemos añadido. Esto significa que no manejamos ni almacenamos ninguna credencial sensible de la tarjeta en nuestros servidores. Hay que pasar por muchos obstáculos para poder hacerlo... ¡así que es estupendo que ni siquiera tengamos que pensar en ello!

Espero que no haga falta decirlo, pero utiliza siempre HTTPS para tus pagos y, en realidad, para todo tu sitio web.

¡De acuerdo! ¡Eso es todo por este curso! ¡Estás listo para empezar a generar beneficios con compras individuales! Aprenderemos más sobre los pagos por suscripción en el próximo curso, así que permanece atento. Y, como siempre, si tienes alguna pregunta que hacernos, estamos a tu disposición en los comentarios. ¡Feliz codificación!
