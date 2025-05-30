# Visualización de los pedidos de LemonSqueezy en la página de cuenta

Últimamente hemos pedido un montón de limonada digital, pero no tenemos una forma cómoda de ver esos pedidos. ¿No sería genial poder ver una lista de ellos en la página de nuestra cuenta? Ahora que hemos establecido una relación entre la entidad `User` y el cliente LemonSqueezy, ¡podemos hacerlo!

## API de LemonSqueezy para obtener pedidos

Empieza abriendo `src/Store/LemonSqueezyApi.php`. Añade un nuevo método -`public function listOrders()` - y devuelve un `array`. Esta función obtendrá los pedidos de la API de LemonSqueezy. Si nos dirigimos a la documentación de LemonSqueezy, en "Listar todos los pedidos", podemos ver que necesitamos utilizar una petición `GET` a la ruta`/orders`.

De vuelta a nuestro nuevo método, añadimos `$response = $this->client->request()`, y dentro,`Request::METHOD_GET` a la ruta `orders`.

Pero espera... no queremos mostrar todos los pedidos -sólo los correspondientes a nuestra tienda y al usuario actual-, así que tenemos que añadir algunos parámetros de consulta adicionales para filtrar esta lista.

## Añadir parámetros de consulta de filtrado

Añadamos un array vacío como tercer argumento al método `request()`, y dentro escribamos `'query' => []`, `'filter' => []`, `'store_id' => $this->storeId`, y `'user_email' => $user->getEmail()`. También tenemos que añadir `User $user` al método`listOrders()` anterior. ¡Perfecto!

A continuación, abre `UserController.php`. Aquí abajo, en `account()`, inyecta`LemonSqueezyApi $lsApi`. También necesitamos el usuario actual, así que añade`#[CurrentUser] User $user`. Abajo, crea la variable `$orders` y ponla en`$lsApi->listOrders()`. Por último, en `return`, pasa `'orders' => $orders`.

## Representación de pedidos y estilo CSS de Tailwind

Ahora tenemos que procesar esos pedidos Abre la plantilla `account.html.twig`... y en algún lugar debajo de `{{ app.user.email }}`, pega este código boilerplate con algo de estilo CSS de Tailwind. Puedes copiarlo de los bloques de código que hay debajo del vídeo.

Como no queremos que todo el mundo vea nuestros pedidos, tenemos que mostrar la lista sólo si `app.user.lsCustomerId` está activado. Si lo está, se mostrará una tabla de pedidos. Si no, sólo mostraremos un mensaje "Todavía no hay pedidos".

Veamos lo que tenemos hasta ahora Volvemos a nuestro sitio, actualizamos la página y... ¡voilá! ¡Nuestra lista de pedidos llena de datos ficticios es visible! Pronto tendremos que sustituir estos datos ficticios por pedidos reales, pero antes, ve a`UserController::account()` y `dd()` la variable `$orders`. Actualiza de nuevo, y... ahí están nuestros "datos" con una matriz de pedidos.

## Utilizar datos dinámicos en la tabla de pedidos

Si hacemos clic en "atributos", veremos un montón de campos que podemos utilizar para nuestra tabla de pedidos. El primero que cogeré es `order_number`. En nuestro código, sustituye `Order`por `#{{ order.attributes.order_number }}`. Para `Date`, sustitúyelo por`{{ order.attributes.created_at|date('d M Y, H:i') }}`. Aquí utilizaremos `|date()`para que la fecha sea más fácil de leer. Tenemos varias opciones para elegir el campo `Amount`. Yo utilizaré `total_formatted` porque está preformateado por LemonSqueezy. Por último, para nuestro enlace, escribiremos`{{ order.attributes.urls.receipt }}`. Antes de probarlo, vuelve a`UserController.php` y elimina el `dd()` que añadimos antes.

## Paginar los pedidos

Lemon Squeezy devuelve 10 pedidos por defecto. Si quieres ver menos de diez a la vez, podemos paginar la lista añadiendo `'page' => ['size' => 5]` a la consulta. Si volvemos atrás y actualizamos... ¡ahora sólo se muestran los 5 últimos pedidos! ¡Funciona!

Lo ideal sería añadir una paginación real a continuación para que los usuarios puedan navegar por todos los pedidos sin salir de nuestro sitio, pero por ahora, vamos a añadir un enlace a la lista completa de pedidos en LemonSqueezy.

En la plantilla, añade un enlace, con el href:`https://app.lemonsqueezy.com/my-orders/{{ (orders.data|first).attributes.identifier|default('') }}`, target: `_blank`, y text: `More Orders`.

Ese enlace preabrirá el último pedido de esa lista para mayor comodidad.

Pero no necesitamos ver este enlace todo el tiempo, sólo si el usuario tiene más de cinco pedidos. Volvamos a `dd($orders)`, actualicemos e inspeccionemos los datos. En `meta`, `page`, vemos `total` y `perPage`, así que volvamos atrás, eliminemos `dd()`, y envolvamos el enlace con`{% if orders.meta.page.total > orders.meta.page.perPage %}`. Arreglaré este espaciado y añadiré `{% endif %}` al final.

Bien, si actualizamos de nuevo y hacemos clic en el enlace... vemos los detalles del último pedido, pero... ¿dónde están los demás? Esto parece ser actualmente una limitación de LemonSqueezy cuando está en modo de prueba. En producción, también se mostrarían todos los pedidos del cliente.

## Evitar filtraciones

Bien, ahora vamos a centrar nuestra atención en un pequeño problema de seguridad. De momento, estamos filtrando los pedidos por el correo electrónico que los usuarios han registrado en su cuenta. Pero, en teoría, los usuarios pueden cambiar su correo electrónico por otro que no posean. Para evitarlo, tenemos que utilizar el correo electrónico establecido en el cliente LemonSqueezy, no en nuestra entidad `User`. Dentro de `LemonSqueezyApi.php`, añade un nuevo`public function` y llámalo `retrieveCustomer()` con `string $customerId`. Dentro de eso, añade`$response = $this->client->request(Request::METHOD_GET, 'customers/' . $customerId)`. Debajo, `return $response->toArray()`.

Arriba, en `listOrders()`, añade `$lsCustomerId = $user->getLsCustomerId()`. Luego, `if (!$lsCustomerId)`, `return []`. Esto garantiza que no haya forma de que un usuario pueda listar pedidos si no tiene un ID de cliente de LemonSqueezy.

A continuación, escribe `$lsCustomer = $this->retrieveCustomer($lsCustomerId)`. Por último, cambia el filtro `user_email` por `$lsCustomer['data']['attributes']['email']`.

¡Seguridad reforzada!

¡Y eso es todo! Has generado con éxito una lista de pedidos en la página de la cuenta utilizando la API de LemonSqueezy.

A continuación: Vamos a realizar algunas mejoras en la gestión de errores de nuestra API, porque se está volviendo molesto depurar manualmente los errores.
