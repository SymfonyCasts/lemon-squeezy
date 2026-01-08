# Escuchar eventos Javascript de LemonSqueezy

Ahora mismo, cada vez que queremos guardar localmente un ID de cliente LemonSqueezy en la entidad de usuario correspondiente, tenemos que configurar nuestros webhooks. Ngrok definitivamente ayuda, pero sigue siendo un poco pesado. Tenemos que ejecutar Ngrok en segundo plano antes de empezar a recibir webhooks. Y aún tenemos que actualizar la URL del webhook cada vez que reiniciamos el agente Ngrok si no tenemos un plan Ngrok de pago. Eso... no es lo ideal.

Exploremos una forma alternativa: escuchar los eventos JavaScript de LemonSqueezy y establecer el ID de cliente en un pago correcto. ¡LemonSqueezy tiene un evento especial para esto! Abre los documentos, ve a "Guías", busca "Usando Lemon.js" a la izquierda, y a la derecha, haz clic en "Manejo de eventos".

Aquí podemos ver que cuando la compra se realiza correctamente, LemonSqueezy lanza un evento`Checkout.Success`. Incluso nos dan un código de ejemplo para manejarlo. Esto devuelve un montón de datos útiles, incluido el ID del cliente que estamos buscando.

## Escuchar el evento LemonSqueezy `Checkout.Success` 

¡Es hora de ponerse manos a la obra! Abre `assets/controllers/lemon-squeezy_controller.js`. Busca el método `connect()` y, al final, empieza con`window.LemonSqueezy.Setup()`. Dentro, pasa `eventHandler: (data) => {}`, y dentro de eso, escribe `if (data.event === 'Checkout.Success')`. Obtén el ID de cliente con `data.data.customer_id` y ponlo en una variable `lsCustomerId`.

[[[ code('1a7d3ed79f') ]]]

Pasaremos el ID a `this.#handleCheckout()`. Éste aún no existe, así que créalo a continuación, con `lsCustomerId` como parámetro.

[[[ code('93940b6eea') ]]]

## Añadir una nueva ruta para crear la URL de pago

A continuación, tenemos que crear una ruta en nuestra aplicación que gestione y guarde el ID de cliente del usuario. Para ello, abre `src/Controller/OrderController.php`y crea un nuevo método: `public function handleCheckout()`. Registra este`#[Route]` con una ruta - `/checkout/handle` y nómbralo`app_order_checkout_handle`. Queremos que este método sólo funcione para peticiones a `POST`.

Esto necesita una petición y el usuario actual, así que inyecta `Request $request` y`#[CurrentUser] User $user`.

[[[ code('3bf285b5e7') ]]]

Supondremos que el ID se pasará a través de una petición POST como `lsCustomerId`, así que recupéralo de la petición con `$request->request->get('lsCustomerId')`. A continuación, establécelo en el usuario con `$user->setLsCustomerId($lsCustomerId)`.

[[[ code('97cce85263') ]]]

Para guardarlo realmente en la base de datos, también tenemos que inyectar`EntityManagerInterface $entityManager` y, al final, llamar a`$entityManager->flush()`. Termina con `return $this->json([])`. Aquí no necesitamos devolver datos reales: basta con una respuesta satisfactoria.

[[[ code('8326a4e668') ]]]

## Actualización del controlador Stimulus

En el controlador Stimulus, añade un nuevo valor llamado`checkoutHandleUrl: String` y pásale la URL de la plantilla. Para ello, en`templates/order/cart.html.twig`, añade`data-lemon-squeezy-checkout-handle-url-value=""` y pasa la URL con`{{ path('app_order_checkout_handle') }}`.

[[[ code('e79883042c') ]]]

[[[ code('0935de6ea7') ]]]

Con el valor establecido, de vuelta en el controlador, en `#handleCheckout()`, haz una llamada AJAX con `fetch()`, pasando`this.checkoutHandleUrlValue`. Para las opciones, utiliza `method: 'POST'`, y para las cabeceras,`'Content-Type': 'application/x-www-form-urlencoded'`. Esto nos permite obtener valores con `$request->request->get()` - sin necesidad de `json_decode()` la petición. Para las `body`, pasa `new URLSearchParams()` con`lsCustomerId: lsCustomerId`.

[[[ code('ce8f98e1d3') ]]]

Encadena esta llamada a `fetch()` con `.then()`. Dentro, espera `response => {}`. Si la respuesta no es correcta, lanza un nuevo `Error()` con el mensaje :

`"Network response was not ok" + response.statusText`.

Abajo, `return response.json()`. Esto nos dará el objeto JSON descodificado en el siguiente `.then()`. Acepta `data => {}`, y dentro, simplemente deja un comentario recordándonos que no hay nada que hacer aquí, porque no devolvemos ningún dato desde esa ruta. Pero, por si acaso algo va mal, encadena`.catch()` con `console.error('Fetch error:', error)`.

[[[ code('a7cbe474bb') ]]]

## Probar y corregir errores

Esto tiene buena pinta, ¡así que vamos a probarlo! En nuestro sitio, añade un producto al carrito y abre la pestaña "Consola" en las Herramientas de desarrollo. Ups... un error.

> Uncaught TypeError: No se pueden leer propiedades de undefined (leyendo 'Setup')

Parece que hemos empezado a utilizar LemonSqueezy más rápido de lo que se descargó su script. Hagamos un pequeño truco y envolvamos este código en`script.addEventListener()`. Escucha el evento `load`, pasa una función como segundo argumento e inserta ahí nuestro código.

[[[ code('bdfcae72b8') ]]]

Si volvemos a actualizar la página... caramba... obtenemos el mismo error.

Vale, parece que primero deberíamos intentar instanciar LemonSqueezy manualmente. Antes de la línea problemática, escribe `window.createLemonSqueezy()`. Añade un pequeño comentario arriba para recordarnos en el futuro lo que estamos haciendo aquí.

[[[ code('d3708a189e') ]]]

Actualiza de nuevo, y... ¡no hay errores! ¡Perfecto! Añadamos rápidamente`console.log(data)` a nuestro código para que sepamos si damos con ese `if` en`Checkout.Success`.

[[[ code('2c08a75e1d') ]]]

Actualiza el sitio una vez más para que se carguen los cambios... y haz clic en "Pagar con LemonSqueezy". Rellena los datos de pago, la dirección de facturación... haz clic en "Pagar", y... ¡veremos el mensaje de éxito! Y en la consola... podemos ver los datos, así que nuestro código ha dado en el clavo. Entonces... ¿funcionó?

En tu terminal, comprueba la base de datos con:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user"
```

¡No ha funcionado! Dice que el valor `lsCustomerId` es "indefinido". Hmmm, parece que estamos utilizando una ruta incorrecta para el ID de cliente. Si volvemos a comprobar nuestro volcado... sí. La ruta que nos dieron los documentos es incorrecta.

Cambia la ruta a `data.data.order.data.attributes.customer_id`, e inténtalo una vez más.

[[[ code('cff195be84') ]]]

Actualiza la página, vuelve a pasar por el proceso de compra (lo haré más rápido para ahorrar tiempo) y... ¡éxito! Ahora, de vuelta en nuestro terminal, vuelve a ejecutar la consulta:

***NOTE
Desde DoctrineBundle 3.0, el comando pasó a llamarse `symfony console dbal:run-sql`
***

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user"
```

Y... ¡Sí! ¡El ID de cliente se ha establecido correctamente! Ya no necesitamos ese `console.log()`, así que podemos borrarlo, junto con otro que se nos pasó en`#openOverlay`.

Así, aunque no tengamos Ngrok en ejecución, podemos sincronizar el ID de cliente de LemonSqueezy con el usuario mediante eventos de JavaScript. Este enfoque simplifica un poco el desarrollo local, pero ambas formas son totalmente válidas.

A continuación: Vamos a abordar algunos posibles problemas de seguridad evitando el secuestro del ID de cliente.
