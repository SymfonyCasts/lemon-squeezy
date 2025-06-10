# Superposición de pago de LemonSqueezy

Los clientes pueden comprar productos en nuestro sitio web, pero para finalizar la compra, les hemos estado redirigiendo al sitio de LemonSqueezy, que está alojado en un dominio completamente distinto. Utilicemos las herramientas JavaScript de LemonSqueezy para mejorar este flujo de trabajo

En lugar de redirigir a los clientes a la página de pago de LemonSqueezy, podemos presentar esa información en una "superposición de pago", un elegante iFrame que vive directamente en nuestro sitio. Así que pongámonos manos a la obra y salpiquemos nuestro sitio web con un poco de magia JavaScript.

## Añadir JavaScript de LemonSqueezy a la página del carrito

En primer lugar, tenemos que añadir la herramienta JavaScript de LemonSqueezy - `lemon.js` - a nuestra página del carrito. Abre `templates/order/cart.html.twig` y añade un nuevo bloque. Llámalo`javascripts`... y ciérralo con `endblock`. Dentro, añade una etiqueta `script`, y establece `src` en `https://app.lemonsqueezy.com/js/lemon.js`. Añade también el atributo`defer`.

[[[ code('3e63a968fd') ]]]

LemonSqueezy aconseja no autoalojar el archivo `lemon.js`, ya que podrías perderte nuevas funciones y parches de seguridad cruciales. Asegúrate de enlazarlo directamente, para mantener los asuntos relacionados con el pago lo más seguros posible.

También tenemos que llamar a la función `{{ parent() }}` dentro de `javascripts` para evitar anular completamente este bloque. Genial.

[[[ code('19c2690867') ]]]

A continuación, añade una clase CSS única al enlace de pago: `lemonsqueezy-button`. Cuando nos dirigimos y actualizamos la página del carrito, es sutil, pero te darás cuenta de que ahora estamos cargando la página de pago de LemonSqueezy bajo nuestra URL. Si inspeccionaras el código fuente, verías que LemonSqueezy está sustituyendo toda la página por su propio contenido. Eso es genial, pero podemos hacerlo aún mejor.

### Crear un controlador especial de Stimulus

Elimina la clase `lemonsqueezy-button` que añadimos antes, y cámbiala por algo un poco más flexible. En `assets/controllers/`, crea un nuevo controlador llamado `lemon-squeezy_controller.js`.

Dentro, añade `import { Controller } from '@hotwired/stimulus'`, y debajo,`export default class extends Controller`. Dentro de la clase, añade dos métodos:`connect()` y `openOverlay()`.

[[[ code('d44113a8f9') ]]]

Ahora, vamos a conectar este controlador en `cart.html.twig`. Añade una nueva línea al enlace de pago y establece `data-controller="lemon-squeezy"`. Esto conecta este enlace a nuestro controlador Stimulus. Debajo, añade `data-action="lemon-squeezy#openOverlay"`, que indica a Stimulus que llame al método `openOverlay()` cuando se haga clic en el enlace.

[[[ code('ee27d7d3e1') ]]]

También necesitamos pasar la URL del Pedido LemonSqueezy, pero en lugar de generarla cada vez que se carga la página del carrito, vamos a generarla sólo cuando se haga clic en el enlace.

### Añadir una nueva acción al OrderController

Necesitamos una nueva acción en `OrderController`. Ve a`src/Controller/OrderController.php` y, justo antes del método `success()`, añade otro: `public function createCheckout()`.

Esto devolverá un `Response`. Encima, añade el atributo `#[Route]` con una ruta - `/checkout/create`. Nómbralo `app_order_checkout_create` y sólo permite los métodos `POST`.

Para las dependencias, inyecta `LemonSqueezyApi $lsApi`, así como el usuario actual con `#[CurrentUser] User $user`.

Dentro, `return $this->json()` con un array: `['targetUrl' => $lsApi->createCheckoutUrl($user)]`.

[[[ code('94d13306e6') ]]]

De vuelta en nuestro controlador `lemon-squeezy` Stimulus, registra un nuevo valor. Escribe`static values = {}`, y dentro, `checkoutCreateUrl: String`.

[[[ code('e4abe6797c') ]]]

En la plantilla del carrito, añade un nuevo atributo de valor de datos -`data-lemon-squeezy-checkout-create-url-value` - y pasa`{{ path('app_order_checkout_create') }}`.

[[[ code('75278c9d7f') ]]]

Dejaré `href` tal cual, para que, si por alguna razón un usuario no tiene JS activado (¿sigue existiendo eso?), pueda realizar la compra. De vuelta a nuestro método`openOverlay()`, añade `e` como parámetro, y luego llama a `e.preventDefault()`para impedir que los navegadores con JS habilitado sigan el enlace.

[[[ code('5904c14bb9') ]]]

Para el resto de este método, coge el elemento enlace con `const linkEl = e.currentTarget`. A continuación, necesitamos ejecutar una petición AJAX al `checkoutCreateUrl` que pasamos como valor. Para ello, utiliza la función `fetch()` para `this.checkoutCreateUrlValue`. Para las opciones, añade `method: 'POST'`, y `headers: {'Content-Type': 'application/json'}`.

[[[ code('f2ba933cc9') ]]]

A continuación, encadena esta llamada a `fetch()` con `.then()`. Dentro, espera un`response`, y añade una comprobación de sanidad - `if (!response.ok)`,`throw new Error()` con `Network response was not OK`, seguido de `response.statusText`.

Si no, `return response.json()`. Eso debería pasar los datos JSON como un objeto al siguiente `.then()`, donde esperamos `data`.

[[[ code('5ae08f4846') ]]]

Vamos a pedir a LemonSqueezy que abra esta URL, así que llama a`window.LemonSqueezy.Url.Open()` y pásale `data.targetUrl`, que devolvimos de la acción `createCheckout()`.

[[[ code('d6866e1fac') ]]]

Por último, añade un `catch()`, esperando un `error`. Dentro, escribe `console.error()` con un mensaje `Fetch error:`, pasando `error` como segundo argumento.

[[[ code('32bee7b7d6') ]]]

Vale, esto tiene buena pinta, así que vamos a probarlo. Abre nuestro sitio, y abre también las Herramientas del desarrollador en la pestaña Consola para ver los registros de JavaScript. Recarga la página y... ¡aquí está nuestro controlador `lemon-squeezy`!

Si hacemos clic en el botón "Pago con LemonSqueezy", se carga y... ¡se abre la página de pago de LemonSqueezy bajo nuestro dominio! ¡Sigue funcionando!

De nuevo, esto es sutil, así que lo siguiente: hagámoslo aún mejor mostrando la página de pago sobre la página de nuestro carrito, en un modal.
