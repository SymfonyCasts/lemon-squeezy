# Escuchar eventos Javascript de LemonSqueezy

Ahora mismo, cada vez que queremos guardar localmente un ID de cliente LemonSqueezy en la entidad de usuario correspondiente, tenemos que configurar nuestros webhooks. Ngrok definitivamente ayuda, pero sigue siendo un poco pesado. Todavía tenemos que ejecutar Ngrok en segundo plano antes de empezar a recibir webhooks, y todavía tenemos que actualizar la URL del webhook cada vez que reiniciamos el agente Ngrok si no tenemos un plan Ngrok de pago. Eso... no es lo ideal.

Exploremos una forma alternativa: escuchar los eventos JavaScript de LemonSqueezy y establecer el ID de cliente en un pago correcto. ¡LemonSqueezy tiene incluso un evento especial para esto! Abre los documentos, ve a "Guías", busca "Usar Lemon.js" a la izquierda, y a la derecha, haz clic en "Manejar eventos".

Aquí podemos ver que cuando la compra se realiza correctamente, LemonSqueezy lanza un evento `Checkout.Success`. Incluso nos dan un código de ejemplo para manejarlo. Esto devuelve un montón de datos útiles, incluido el ID del cliente que estamos buscando.

## Escuchar el evento LemonSqueezy `Checkout.Success` 

¡Es hora de ponerse manos a la obra! Abre `assets/controllers/lemon-squeezy_controller.js`. Busca el método `connect()` y, en la parte inferior, empieza con `window.LemonSqueezy.Setup()`. Dentro, pasa a `eventHandler: (data) => {}`, y dentro de éste, escribe `if (data.event === 'Checkout.Success')`. Ahora necesitamos obtener el ID del cliente con `data.data.customer_id` y ponerlo en una variable `lsCustomerId`. Pasaremos el ID a `this.#handleCheckout()`. Esto aún no existe, pero lo crearemos en un momento. Por último, crea la función `#handleCheckout()` con `lsCustomerId` y déjala vacía por ahora. 

## Añadir una nueva ruta para crear la URL de pago

A continuación, tenemos que crear una ruta en nuestra aplicación que gestione y guarde el ID de cliente del usuario. Para ello, abre `src/Controller/OrderController.php` y crea un nuevo método: `public function handleCheckout()`. Registra este `#[Route]` con una ruta - `/checkout/handle` - y llámalo `app_order_checkout_handle`. Queremos que este método sólo funcione para peticiones a `POST`.

Esto necesita una petición y el usuario actual, así que inyecta `Request $request` y el atributo PHP `#[CurrentUser]` con `User $user`. Supondremos que el ID se pasará a través de una petición POST como `lsCustomerId`, así que lo recuperaremos de la petición con `$request->request->get('lsCustomerId')`.

A continuación, establécelo en el usuario con `$user->setLsCustomerId($lsCustomerId)`. Para guardarlo realmente en la base de datos, también tenemos que inyectar `EntityManagerInterface $entityManager` y, al final, llamar a `$entityManager->flush()`. Termina con `return $this->json([])`. Aquí no necesitamos devolver datos reales: basta con una respuesta satisfactoria.

## Actualización del controlador Stimulus

Para el controlador Stimulus, vamos a añadir un nuevo valor llamado `checkoutHandleUrl: String` y a pasarle la URL de la plantilla. Para ello, en `templates/order/cart.html.twig`, añade `data-lemon-squeezy-checkout-handle-url-value=""` y pasa la URL con `{{ path('app_order_checkout_handle') }}`.

Con el valor establecido, de vuelta en el controlador, hagamos una llamada AJAX en `#handleCheckout()` utilizando el método `fetch()`. Establécelo en `this.checkoutHandleUrlValue`. Para las opciones, utiliza `method: 'POST'`, como configuramos en nuestra ruta, y para las cabeceras, `'Content-Type': 'application/x-www-form-urlencoded'`. Esto nos permite obtener valores con `$request->request->get()` - sin necesidad de `json_decode()` la petición.

Para el `body`, pasa `new URLSearchParams()` y pasa `lsCustomerId: lsCustomerId` a eso. También encadenaremos esta llamada a `fetch()` con `.then()`. Dentro, esperamos `response => {}`. Si la respuesta no es correcta, lanzaremos un nuevo `Error()` con un mensaje :

`"Network response was not ok" + response.statusText`.

A continuación, `return response.json()`. Eso nos dará el objeto JSON descodificado en el siguiente `.then()`. Digamos `data => {}`, y dentro, simplemente dejaré un comentario recordándonos que no hay nada que hacer aquí, porque no devolvemos ningún dato desde esa ruta. Pero, por si acaso algo sale mal, encadenaremos `.catch()` con `console.error('Fetch error:', error)`.

## Probar y corregir errores

Esto tiene buena pinta, ¡así que vamos a probarlo! En nuestro sitio, añade un producto al carrito y abre la pestaña "Consola" en las Herramientas de desarrollo de Chrome. Ups... un error.

> Uncaught TypeError: No se pueden leer propiedades de undefined (leyendo 'Setup')

Parece que hemos empezado a utilizar LemonSqueezy más rápido de lo que se descargó su script. Hagamos un pequeño truco y envolvamos este código con `script.addEventListener()`. Queremos escuchar el `load`, pasar una función como segundo argumento, e insertar ahí nuestro código.

Si volvemos a actualizar la página... caramba... obtenemos el mismo error.

Vale, parece que primero deberíamos intentar instanciar LemonSqueezy manualmente. Antes de la línea del problema, escribe `window.createLemonSqueezy()`. También añadiré un pequeño comentario arriba para recordarnos en el futuro lo que estamos haciendo aquí.

Actualiza de nuevo, y... ¡no hay errores! ¡Perfecto! Añadamos rápidamente `console.log(data)` a nuestro código para saber si nos encontramos con ese `if` en `Checkout.Success`. Actualiza nuestro sitio una vez más para que se carguen los cambios... y haz clic en "Pagar con LemonSqueezy". Rellenamos la información de pago y la dirección de facturación... hacemos clic en "Pagar", y... ¡vemos el mensaje de éxito! Y en la consola... podemos ver los datos, así que nuestro código ha dado en el clavo. Entonces... ¿funcionó?

En tu terminal, comprueba la base de datos con:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user"
```

¡No ha funcionado! Dice que el valor `lsCustomerId` es "indefinido". Hmmm, parece que estamos utilizando la ruta incorrecta para el ID de cliente. Si volvemos a comprobar nuestro volcado... sí. La ruta que nos dio Docs es incorrecta.

Cambia la ruta a `data.data.order.data.attributes.customer_id`, e intentémoslo una vez más. Actualiza la página, vuelve a pasar por el proceso de compra (lo haré más rápido para ahorrar tiempo) y... ¡éxito! Ahora, de vuelta en nuestro terminal, vuelve a ejecutar la consulta:

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user"
```

¡Sí! ¡El ID de cliente se ha establecido correctamente! Ya no necesitamos ese `console.log()`, así que podemos borrarlo, junto con otro que se nos pasó en `#openOverlay`.

Así que, aunque no tengamos Ngrok en ejecución, podemos sincronizar el ID de cliente de LemonSqueezy con el usuario mediante eventos de JavaScript. Este enfoque simplifica un poco el desarrollo local, pero ambas formas son totalmente válidas.

A continuación: Vamos a abordar algunos posibles problemas de seguridad evitando el secuestro del ID de cliente.
