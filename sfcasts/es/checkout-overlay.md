# Superposición de pago de LemonSqueezy

Los clientes pueden comprar productos en nuestro sitio web, pero para finalizar la compra, les hemos estado redirigiendo al sitio de LemonSqueezy, que está alojado en un dominio completamente distinto. Utilicemos las herramientas JavaScript de LemonSqueezy para mejorar este flujo de trabajo

En lugar de redirigir a los clientes a la página de pago de LemonSqueezy, podemos presentar esa información en una "superposición de pago", un elegante iFrame que vive directamente en nuestro sitio. Así que pongámonos manos a la obra y salpiquemos nuestro sitio web con un poco de magia JavaScript.

## Añadir JavaScript de LemonSqueezy a la página del carrito

En primer lugar, tenemos que añadir la herramienta JavaScript de LemonSqueezy - `lemon.js` - a nuestra página del carrito. Abre `templates/order/cart.html.twig` y añade un nuevo bloque. Llámalo `javascripts`... y ciérralo con `endblock`. Dentro, añade una etiqueta `script`, y establece `src` en `https://app.lemonsqueezy.com/js/lemon.js`. También añadiremos el atributo HTML `defer`.

LemonSqueezy desaconseja autoalojar el archivo `lemon.js`, ya que podrías perderte nuevas funciones y parches de seguridad cruciales, así que vamos a enlazarlo directamente, para mantener los asuntos relacionados con el pago lo más seguros posible.

También tenemos que llamar a la función `{{ parent() }}` dentro de `javascripts` para evitar sobrescribir este bloque. Genial.

A continuación, añade una clase CSS única al enlace de pago: `lemonsqueezy-button`. Cuando actualicemos la página del carrito, verás que ahora se carga la página de pago de LemonSqueezy bajo nuestra URL. Si inspeccionaras el código fuente, verías que LemonSqueezy está sustituyendo toda la página por su propio contenido. Eso es genial, pero podemos hacerlo aún mejor.

### Crear un controlador especial de Stimulus

Elimina la clase `lemonsqueezy-button` que añadimos antes, y cámbiala por algo un poco más flexible. En `assets/controllers/`, crea un nuevo controlador. Lo llamaremos `lemon-squeezy_controller.js`. 

Dentro, añade `import { Controller } from '@hotwired/stimulus'`, y debajo, `export default class extends Controller`. Dentro de la clase, añade un método `connect()`, que dejaremos vacío por ahora. Por último, añade otro método - `#openOverlay()` - que será una acción Stimulus. 

Ahora, vamos a conectar este controlador en `cart.html.twig`. Añade una nueva línea al enlace de pago con `data-action`, para que cuando hagamos clic en este botón, llame a la acción `#openOverlay()`.

También tenemos que pasar la URL de pago de LemonSqueezy, pero en lugar de generarla cada vez que se cargue la página del carrito, vamos a generarla cuando se haga clic en el enlace.

### Añadir una nueva acción al OrderController

Añade una nueva acción a `OrderController`. Ve a`src/Controller/OrderController.php` y, justo antes de la acción `success()`, añade otra. La llamaremos `public function createCheckout()`. 

Ésta devolverá un `Response`, y añadiremos un atributo `#[Route]` encima con una ruta - `/checkout/create`. Llámala `app_order_checkout_create` y sólo permite métodos `POST`.

Para las dependencias, necesitaremos `LemonSqueezyApi $lsApi`, así como un usuario que haya iniciado sesión. Para ello, añade el atributo `#[CurrentUser]` con `User $user`. 

En el método, simplemente devuelve el JSON con un array vacío. Luego, en el array, añade una clave `targetUrl`, llama a `$lsApi->createCheckoutUrl()` para el valor, y pasa el usuario. ¡Listo!

De vuelta en nuestro controlador `lemon-squeezy`, registra un nuevo valor. Digamos `static values = {}`, y dentro, añade `checkoutCreateUrl: String`. 

En la plantilla del carrito, añade un nuevo atributo de valor de datos - `data-lemon-squeezy-checkout-create-url-value` - y pasa `{{ path('app_order_checkout_create') }}`.

También puedes sustituir `href` por un '#' si quieres evitar que se haga clic en él si JavaScript está deshabilitado, pero lo dejaré para el legado. En su lugar, en `#openOverlay()`, cogeremos el evento y llamaremos a `e.preventDefault()`.

Bien, a continuación, vamos a implementar el método `#openOverlay()`. Aquí abajo, coge el elemento enlace con `const linkEl = e.currentTarget`. Debajo, necesitamos ejecutar una petición AJAX al `checkoutCreateUrl` que pasamos como valor. Para ello, utiliza la función `fetch()`. Dentro, llama a `this.checkoutCreateUrlValue`, y añade las opciones como segundo argumento. Esta petición AJAX debe ejecutarse con `method: 'POST'`... y para las cabeceras, establece `Content-Type` en `application/json`. 

A continuación, encadenaremos esta llamada a `fetch()` con `.then()`. Dentro, esperamos una `response`, y también añadiremos una comprobación de sanidad - `if (!response.ok)`, `throw new Error()` - que nos dirá que la `Network response was not OK`, seguida de `response.statusText`. 

De lo contrario, sólo `return response.json()`. Eso debería pasar los datos JSON como un objeto al siguiente `.then()`, donde esperamos `data => {}`.

Vamos a pedir a LemonSqueezy que abra esta URL, así que llama a `window.LemonSqueezy.Url.Open` y pásale el `data.targetUrl`, que devolveremos de la acción `createCheckout()`. 

Por último, podemos añadir una llamada a `catch()`, esperando un error. Dentro, sólo diremos `console.error()` con un mensaje `Fetch error:`, pasando `error` como segundo argumento.

Vale, esto tiene buena pinta, así que vamos a probarlo. Abre nuestro sitio, y abre también las Herramientas para desarrolladores de Chrome en la pestaña Consola para ver los registros de JavaScript. Recarga la página y... ¡aquí está nuestro controlador `lemon-squeezy`! 

Si hacemos clic en el botón "Pago con LemonSqueezy", se carga y... ¡se abre la página de pago de LemonSqueezy bajo nuestro dominio! ¡Sigue funcionando!

Siguiente: Hagamos que esto sea aún más genial renderizando la página de pago de LemonSqueezy sobre nuestra página del carrito.
