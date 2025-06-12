# Sincronizar el ID de cliente mediante un evento JavaScript y mejorar la seguridad

¡Bienvenido de nuevo! Acabamos de sincronizar el identificador de cliente de LemonSqueezy con el usuario de nuestra base de datos utilizando dos métodos diferentes: a través de webhooks, con lo que conseguimos una configuración de producción bastante sólida, y a través de eventos JavaScript de LemonSqueezy, que nos ayudan a saltarnos la configuración de Ngrok y webhook localmente. Es perfectamente aceptable utilizar ambos métodos simultáneamente.

Pero, dediquemos un momento a examinar nuestra acción `handleCheckout()`. Puede que tengamos un posible problema de seguridad entre manos. Los usuarios malintencionados podrían intentar enviar una petición AJAX a esta ruta utilizando un ID de cliente de LemonSqueezy diferente. Esto podría anular su propio ID de cliente, lo que podría llevar a una situación en la que nuestra aplicación generara una URL firmada para ese cliente y se la entregara al atacante. Esto les permitiría ver información personal, hacer cambios en nombre del cliente e incluso realizar compras fraudulentas.

Pero, ¡no te preocupes! ¡Tenemos algunas soluciones! Podríamos utilizar la sincronización de clientes a través de los eventos JavaScript sólo en modo dev. Esto significa que no funcionará en producción, pero sí localmente, y que los usuarios reales sólo se sincronizarán mediante webhooks con firma firmada en producción.

Como alternativa, podríamos añadir comprobaciones adicionales a la acción `handleCheckout()` para, por ejemplo, verificar si el ID de usuario actual se corresponde con el ID de usuario establecido en los datos personalizados del evento LemonSqueezy. Exploremos esta opción y veamos cómo podemos evitar que la gente anule los ID de cliente con datos corruptos.

## Añadir comprobaciones adicionales para evitar la anulación de datos

Abre `lemons-squeezy_controller.js`. En `LemonSqueezy.Setup()`, puedes descomentar la línea `console.log(data)` para depurar la respuesta y encontrar la estructura de la ruta para el ID de usuario. O, si quieres saltarte esa parte, puedes confiar en mí y escribir `const userId = data.data.order.meta.custom_data.user_id`.

A continuación, pasa esta variable `userId` como primer argumento al método `#handleCheckout()`. En `#handleCheckout()`, cambia la firma a `userId lsCustomerId` y, aquí abajo, pasa la `userId` al objeto `URLSearchParams()`, igual que hicimos con `lsCustomerId`.

De vuelta en `OrderController.php`, en la parte superior de `Response`, crea una variable `$userId` y hazla igual a `$request->request->get('userId')`, ya que estamos tratando con una petición `POST`. Esto también es bastante similar a lo que hicimos con `lsCustomerId`.

Debajo de eso, añade una declaración `if`: `if ($userId !== $user->getId())`. Como el método `getId()` devuelve un número entero, y como me encanta la comparación estricta, vamos a encasillarlo en `string`.

Si se cumple esta condición, `throw $this->createAccessDeniedException()`. Dentro, escribiremos una función `sprintf()`, que diga:

> ¡El ID de usuario actual "%s" no coincide con el ID de usuario "%s" del pedido!

Pasaremos `$user->getId()` y `$userId` como argumentos.

Así, si hay una falta de coincidencia de ID, golpearemos esta declaración `if` y lanzaremos una excepción, para que podamos verlo en nuestros registros. Ahora podemos establecer con seguridad el cliente, ya que estamos seguros de que está relacionado con el usuario actual.

## Probar nuestra configuración

Dirígete a tu terminal y ejecuta:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user"
```

Tenemos el `lsCustomerId` configurado, como esperábamos. Ahora, volvamos a ejecutar el mismo comando con una nueva consulta:

```terminal
bin/console doctrine:query:sql "UPDATE user SET lsCustomerId=NULL WHERE id=1"
```

Es una buena práctica añadir siempre una cláusula `WHERE` a tus consultas `UPDATE` o `DELETE`, así evitarás actualizar accidentalmente todos tus registros si tienes más de un registro en la tabla.

¡Vamos a probarlo! En la página del carrito, haz clic en el botón "Pagar", rellena los datos de facturación... y haz clic en "Pagar". Si esperamos un momento... veremos el mensaje "Gracias por su pedido". Ya tenemos un nuevo pedido en nuestra cuenta.

De vuelta al terminal, ejecuta de nuevo la consulta `SELECT`:

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user"
```

Aquí podemos ver que el campo `lsCustomerId` está configurado de nuevo. En este momento no estamos ejecutando túneles Ngrok, por lo que se estableció a través del evento JavaScript. ¡Funciona!

## Utiliza siempre HTTPS

¡Ahí lo tienes! Hemos visto cómo LemonSqueezy gestiona las compras. Las credenciales del carrito nunca se envían a nuestro servidor, sino que se envían directamente al servidor de LemonSqueezy a través del iFrame que hemos añadido. Eso significa que no manejamos ni almacenamos ninguna credencial sensible de la tarjeta en nuestros servidores. ¡Sí! Y recuerda utilizar siempre HTTPS para tu pago. Sinceramente, es mejor utilizarlo en todo tu sitio web. No sólo es una práctica habitual, sino que también aumenta significativamente la seguridad de tu sitio y protege a los usuarios que hacen que tu negocio funcione.

Muy bien ¡Eso es todo por este curso! ¡Estás listo para empezar a generar beneficios con compras individuales! Aprenderemos más sobre los pagos por suscripción en el próximo curso, así que permanece atento. Y, como siempre, si tienes alguna pregunta que hacernos, estamos a tu disposición en los comentarios. ¡Feliz codificación!
