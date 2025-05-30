# Visualización de los pedidos de LemonSqueezy en la página de cuenta

Últimamente hemos pedido un montón de limonadas digitales, pero no tenemos una forma cómoda de ver esos pedidos. ¿No sería genial poder ver una lista de ellos en la página de cuenta de nuestro sitio web? Ahora que hemos establecido una relación entre la entidad `User` y el cliente LemonSqueezy, ¡podemos hacerlo!

## API de LemonSqueezy para obtener Pedidos

Empieza abriendo `src/Store/LemonSqueezyApi.php`. Añade un nuevo método - `public function listOrders()` - y devuelve un array. Esta función obtendrá los pedidos de la API de LemonSqueezy. Si nos dirigimos a la documentación de LemonSqueezy, en "Listar todos los pedidos", podemos ver que necesitamos utilizar una petición `GET` a la ruta `/orders`.

De vuelta a nuestro nuevo método, añadimos `$response = $this->client->request()`, y dentro, `Request::METHOD_GET` a la ruta `orders`.

Pero espera... no queremos mostrar todos los pedidos -sólo los correspondientes a nuestra tienda y al usuario actual-, así que tenemos que añadir algunos parámetros de consulta adicionales para filtrar esta lista.

## Añadir parámetros de consulta de filtrado

Añadamos una matriz vacía como tercer argumento al método `request()`, y dentro digamos `'query' => []`, `'filter' => []`, `'store_id' => $this->storeId`, y `'user_email' => $user->getEmail()`. También tenemos que añadir `User $user` al método `listOrders()` anterior. ¡Perfecto!

A continuación, abre `UserController.php`. Aquí abajo, en `account()`, inyecta `LemonSqueezyApi $lsApi`. También necesitamos el usuario actual, así que añade `#[CurrentUser] User $user`. Abajo, crea la variable `$orders` y ponla en `$lsApi->listOrders()`. Por último, en `return`, pasa `'orders' => $orders`.

## Representación de pedidos y estilo CSS de Tailwind

Ahora tenemos que procesar esos pedidos Abre la plantilla `account.html.twig`... y en algún lugar debajo de `{{ app.user.email }}`, pega algo de código boilerplate con algo de estilo CSS de Tailwind. Puedes copiarlo de los bloques de código que hay debajo del vídeo.

Como no queremos que todo el mundo vea nuestros pedidos, tenemos que mostrar la lista sólo si `app.user.lsCustomerId` está activado. Si lo está, se mostrará la tabla de pedidos. Si no, sólo mostraremos el mensaje "Aún no hay pedidos".

Veamos lo que tenemos hasta ahora Volvemos a nuestro sitio, actualizamos la página y... ¡voilá! ¡Nuestra lista de pedidos llena de datos ficticios es visible! Pronto tendremos que sustituir estos datos ficticios por pedidos reales, pero antes, ve a `UserController::account()` y `dd()` la variable `$orders`. Actualiza de nuevo, y... ahí están nuestros "datos" con una matriz de pedidos.

## Utilizar datos dinámicos en la tabla de pedidos

Si hacemos clic en "atributos", veremos un montón de campos que podemos utilizar para nuestra tabla de pedidos. El primero que cogeré es `order_number`. En nuestro código, sustituye `Order` por `#{{ order.attributes.order_number }}`. Para `Date`, sustitúyelo por `{{ order.attributes.created_at|date('d M Y, H:i') }}`. Aquí utilizaremos `|date()` para que el campo de fecha sea más fácil de leer. Tenemos varias opciones para elegir el campo `Amount`. Yo utilizaré `total_formatted` porque está preformateado por LemonSqueezy. Por último, para nuestro enlace, diremos `{{ order.attributes.urls.receipt }}`. Antes de probarlo, vuelve a `UserController.php` y elimina el `dd()` que añadimos antes.

## Paginar los pedidos

Lemon Squeezy devuelve 10 pedidos por defecto. Si quieres ver menos de diez a la vez, podemos paginar la lista añadiendo `'page' => ['size' => 5]` a la consulta. Si volvemos atrás y actualizamos... ¡ahora sólo se muestran los 5 últimos pedidos! ¡Funciona!

Lo ideal sería añadir una paginación real a continuación para que los usuarios puedan navegar por todos los pedidos sin salir de nuestro sitio, pero por ahora, vamos a añadir simplemente un enlace a la lista completa de pedidos en LemonSqueezy.

En la plantilla, añade:

`<a href="https://app.lemonsqueezy.com/my-orders/{{ (orders.data|first).attributes.identifier|default('') }}" target="_blank">More Orders</a>`.

Ese enlace preabrirá el último pedido de esa lista para mayor comodidad.

Pero no necesitamos ver este enlace todo el tiempo, sólo si el usuario tiene más de cinco pedidos. Volvamos a `dd($orders)`, actualicemos e inspeccionemos los datos. En `meta` `page` , vemos `total` y `perPage`, así que vuelve atrás, elimina `dd()`, y envuelve el enlace con `{% if orders.meta.page.total > orders.meta.page.perPage %}`. Arreglaré este espaciado y añadiré `{% endif %}` al final.

Vale, si actualizamos de nuevo y hacemos clic en el enlace... ¿sólo aparece el último pedido de la lista? Sí... sobre eso - esto no funciona en modo de prueba porque LemonSqueezy sólo muestra aquí los pedidos de producción. Espero que LemonSqueezy lo arregle pronto, pero por ahora, sólo podemos ver los pedidos que enlazamos directamente en esta página o ver esto en acción en el modo de producción.

## Evitar filtraciones

Bien, ahora vamos a centrar nuestra atención en un pequeño problema de seguridad. De momento, estamos filtrando los pedidos por el correo electrónico que los usuarios han registrado en su cuenta. Pero, en teoría, los usuarios pueden cambiar su correo electrónico por otro que no posean. Para evitarlo, tenemos que utilizar el correo electrónico establecido en el cliente LemonSqueezy, no en nuestra entidad `User`. Dentro de `LemonSqueezyApi.php`, añade un nuevo `public function` y llámalo `retrieveCustomer()` con `string $customerId`. Dentro de eso, añade `$response = $this->client->request(Request::METHOD_GET, 'customers/' . $customerId)`. Abajo, `return $response->toArray()`.

Arriba, en `listOrders()`, pongamos el correo electrónico del usuario en una variable separada con `$userEmail = $user->getEmail()` y cambiémoslo a `$userEmail`. Por último, aquí abajo, añade una declaración `if`:

`if ($user->getLsCustomerId()) {
    $lsCustomer = $this->retrieveCustomer($user->getLsCustomerId());
    $userEmail = $lsCustomer['data']['attributes']['email'];
}`

Esto garantiza que sólo cargamos pedidos para el correo electrónico almacenado en LemonSqueezy. Técnicamente alguien podría seguir interfiriendo en esto, pero ahora es mucho más difícil hacerlo.

También sugiero añadir la verificación del correo electrónico de los usuarios, para que podamos estar absolutamente seguros de que son propietarios del correo electrónico que utilizan. Y oye, ¡puedes aprovechar [SymfonyCasts/verify-email-bundle](https://github.com/SymfonyCasts/verify-email-bundle) para esto!

También hemos hecho que `lsСustomerId` sea un campo único en la entidad `User`, por lo que la petición del webhook fallará y no podrá actualizar el cliente en el proceso del webhook si existe alguien con el mismo ID de cliente en la base de datos, haciendo que secuestrar la identidad de alguien sea casi imposible.

¡Y eso es todo! Has generado con éxito una lista de pedidos en la página de la cuenta utilizando la API de LemonSqueezy.

A continuación: Hagamos algunas mejoras en la gestión de errores de nuestra API, porque se está volviendo molesto depurar manualmente los errores.
