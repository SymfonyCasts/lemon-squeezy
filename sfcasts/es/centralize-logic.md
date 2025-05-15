# Centralizar la lógica empresarial de LemonSqueezy

Hasta ahora, nos hemos centrado en poner en marcha nuestra caja, ¡y lo hemos conseguido! Pero nuestro código está un poco disperso por el controlador. ¿No sería mucho más cómodo tener todo lo relacionado con la API de LemonSqueezy en una clase aparte? ¡Claro que sí! ¡Así que vamos a organizarnos!

Tenemos que localizar todo el código relacionado con LemonSqueezy en el controlador y trasladarlo a un servicio independiente para que sea más fácil de mantener, reutilizar y probar. Para ello, en `src/Store/`, crea una nueva clase llamada `LemonSqueezyApi`. Conviértela en`final readonly`. Ahora podemos mover nuestro método `createLsCheckoutUrl()`. Copiaré este gran bloque, lo eliminaré y lo pegaré en nuestra nueva clase, y esta vez lo llamaré `public`. Como sabemos que está relacionado con LemonSqueezy porque está en`LemonSqueezyApi`, podemos cambiarle el nombre a `createCheckoutUrl()` para que sea más sencillo.

[[[ code('b9f89467cc') ]]]

A continuación, vamos a coger `$lsClient` y `$cart`, y encima, convertirlos en dependencias del constructor con `public function __construct()`. Pega, también simplificaremos `$lsClient` y lo llamaremos simplemente `$client`. Por encima de este argumento, añade`#[Target('lemonSqueezyClient')]`, añade `private` antes de cada propiedad.

[[[ code('396ab625f1') ]]]

Y por último, cambia esta variable `$cart` por una propiedad con `$this->cart`. Haremos lo mismo con el resto de variables `$cart`. Y ya que estamos aquí, también cambiaremos `$lsClient` por `$this->client`. Bien.

[[[ code('455b73f7b6') ]]]

Ahora necesitamos un servicio para generar URLs. Podemos inyectarlo en el constructor con `UrlGeneratorInterface $urlGenerator`. A continuación, sustituye `$this->generateUrl()`por `$this->urlGenerator->generate()`. 

[[[ code('30b5741180') ]]]

También necesitamos acceder a los parámetros. Podríamos inyectar todo el servicio `ParameterBagInterface`, que nos permite acceder a cualquier parámetro, pero como sólo necesitamos uno - `storeId` - vamos a inyectarlo directamente.

En nuestro constructor, añade: `private readonly string $storeId,`. Encima, añade el atributo PHP con `#[Autowire('%env(LEMON_SQUEEZY_STORE_ID)%')]`. Y por último, sustituye cada instancia de `$this->getParameter()` por `$this->storeId`. Sólo lo veo una vez aquí, así que es bastante fácil.

[[[ code('26c651fc19') ]]]

Ahora, de vuelta en `OrderController::checkout()`, deshagámonos de estas dependencias no utilizadas e inyectemos `LemonSqueezyApi $lsApi` en su lugar. A continuación, utiliza el servicio con `$lsCheckoutUrl = $lsApi->createCheckoutUrl();`.

[[[ code('5cfb4cf46d') ]]]

¡Hora de probar! Asegurémonos de que aún podemos pagar. En nuestro sitio, recarga, selecciona "Limonada Clásica", añade una al carrito y haz clic en "Pagar con LemonSqueezy". ¡Sí! Estamos en la página de pago de LemonSqueezy y todo parece perfecto

Vale, ahora que sabemos que la compra funciona, ¿podemos hacer que `$lsStoreUrl` en el método`success()` sea dinámico? ¡Pues sí! Y LemonSqueezy tiene una ruta API precisamente para eso En los documentos de la API, busca la ruta "Recuperar una tienda"... y mira la respuesta de ejemplo de la derecha. Parece que podemos leer la URL desde `attributes`, así que, de vuelta a nuestro código, en`LemonSqueezyApi`, crea un nuevo método público. Llámalo `retrieveStoreUrl()`, y haz que devuelva un `string`. Dentro, añade`$response = $this->client->request(Request::METHOD_GET, 'stores/' . $this->storeId)`. Debajo, escribe `$lsStore = $response->toArray()` y, por último,`return $lsStore['data']['attributes']['url']`.

[[[ code('82abf9fce8') ]]]

De vuelta en el método `success()`, inyecta `LemonSqueezyApi $lsApi,` y sustituye esta URL codificada por `$lsStoreUrl = $lsApi->retrieveStoreUrl()`. 

[[[ code('ab9956a432') ]]]

¡Es hora de hacer otra prueba! De vuelta a nuestro sitio, elige una de nuestras deliciosas limonadas -esta vez elegiré la de manzana- y añádela al carrito. En la página del carrito, haz clic de nuevo en el botón "Realizar pedido", rellena nuestras credenciales y la dirección de facturación, haz clic en "Pagar" y, por último, en el modal "correcto", haz clic en "Continuar". ¡Listo! ¡Aquí está nuestro mensaje flash! ¡Sigue funcionando!

A continuación: Asignemos un cliente de LemonSqueezy al usuario correspondiente en nuestro sistema para saber qué compras ha realizado.
