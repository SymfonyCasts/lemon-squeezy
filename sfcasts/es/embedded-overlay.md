# Incrustar la superposición de pago LemonSqueezy

En el último capítulo, nos ensuciamos las manos y construimos un controlador Stimulus personalizado de LemonSqueezy. Muestra la página de pago de LemonSqueezy en un iFrame directamente en nuestro dominio. Eso está muy bien, pero ¿y si te dijera que podemos hacerlo aún mejor? Vamos a colocar la página de pago sobre la página de nuestro carrito y crear una verdadera superposición.

Pero antes de lanzarnos, vamos a añadir un par de funciones clave al botón "Pagar con LemonSqueezy":
* Evitar los dobles clics
* Y mostrar el progreso de carga

## Evitar los dobles clics y mostrar el progreso de carga

Para empezar, abre `lemon-squeezy_controller`. Vamos a crear aquí algunos métodos privados. Empieza con `#disableLink()` y pásale un argumento `link`. A continuación, añade `#enableLink()` y vuelve a pasar `link` como argumento. 

Para `#disableLink()`, escribiremos algo de código para añadir la clase CSS `disabled` al enlace, que desactivará los eventos de puntero y atenuará ligeramente el enlace. Decimos `link.classList.add('disabled')`, luego `link.style.pointerEvents ='none'`, y terminamos con `link.style.opacity = '0.5'`.

En `#enableLink()`, haremos lo contrario. Escribe `link.classList.remove('disabled')`, `link.style.pointerEvents = 'auto'`, y `link.style.opacity = '1'`.

Bien, ahora, en la acción `#openOverlay()`, justo después de crear `linkEl`, llama a `this.#disableLink(linkEl)`. Por si acaso algo no sale según lo previsto, en el segundo `.then()` después de `window.LemonSqueezy.Url.Open(data.targetUrl)`, llama a `this.#enableLink(linkEl)`. Haremos lo mismo en `.catch()` después de `console.log()`.

Muy bien, en nuestro sitio, recargamos la página del carrito, y si hacemos clic varias veces en el botón "Realizar pedido con LemonSqueezy"... podemos ver que está ligeramente atenuado e ignora por completo nuestros dobles clics. ¡Qué bien!

## Integrar la página de pago

Ahora, la parte divertida: ¡incrustar! Abre `src/Store/LemonSqueezyApi.php` y, en `createCheckoutUrl()`, después de configurar el ID de usuario personalizado, añade `$attributes['checkout_options']['embed'] = true`.

Actualiza la página del carrito, haz clic de nuevo en el botón de pago y... ¡ahí está: una nueva y brillante superposición de LemonSqueezy en nuestra página del carrito! Incluso podemos ver nuestra página del carrito en segundo plano si pulsamos el botón de cerrar. Cuando lo cerremos, nuestro botón "Pagar con LemonSqueezy" estará listo de nuevo.

## Mejorando `createCheckoutUrl()`

De momento, estamos llamando a `createCheckoutUrl()` en un par de sitios: en `OrderController::createCheckout()` y de nuevo en `OrderController::checkout()`. Si queremos utilizar la incrustación sólo para la versión JavaScript, podemos añadir un argumento booleano `$embed` a `LemonSqueezyApi::createCheckoutUrl()` que por defecto es `false`. También sustituiremos el código duro `true` que utilizamos antes por la nueva variable `$embed`. De vuelta en `OrderController`, pasaremos `true` a `createCheckoutUrl()` en la acción `createCheckout()`. 

## Automatizar la inclusión de `lemon.js` 

Para asegurarnos de que nuestro controlador LemonSqueezy Stimulus funciona, tenemos que incluir `lemon.js` en cada página que lo utilicemos. Si eso suena tedioso, lo es, así que vamos a automatizarlo.

En el método `connect()`, crea una variable `script`, hazla igual a `window.document.querySelector()`, y pasa `'script[src="https://app.lemonsqueezy.com/js/lemon.js"]'` como argumento. Si la etiqueta `script` no existe, la crearemos y la añadiremos al DOM. Fácil, ¿verdad? Dentro de `if`, escribe `script = window.document.createElement()` con una etiqueta `script` dentro. Establece también `script.src` en la URL `lemon.js`, y no olvides establecer el atributo HTML defer en `true`. Por último, añade `window.document.head.appendChild(script)`.

Ahora podemos celebrarlo eliminando todo el bloque `javascripts` de la plantilla, e intentar realizar la compra de nuevo. Recarga la página del carrito, haz clic en el botón de pago, podemos ver que se está cargando, y... ¡sí! ¡Todavía podemos ver la superposición!

## Depuración para usuarios no autenticados

Pero hay un problema para los usuarios no autenticados. Si cerramos la sesión, añadimos un producto al carrito e intentamos pagar de nuevo... no ocurre nada. Si abres las Herramientas de desarrollo de Chrome, puedes ver que la petición se redirige primero a una página de inicio de sesión, pero nuestra lógica JavaScript no sigue esa redirección. ¡Vamos a arreglarlo!

En nuestro código, añade un `console.log(response)` antes de la comprobación `response.ok`. De vuelta en nuestro sitio, en la pestaña "Consola", podemos ver que `response.redirected` se establece en `true` para esa petición. Añadamos un `if` más - `if (response.redirected === true)` - y redirigiremos al usuario a la página de inicio de sesión con `window.location.href = response.url`. Si el usuario no está autenticado, añadiremos `Promise.reject()`, que nos dirá que el `User is not authenticated!`. También añadiré un comentario rápido más arriba.

## Redirigir a los usuarios a la página del carrito

Vale, ahora si hacemos clic en el botón de compra cuando no estamos autenticados... ¡seremos redirigidos a la página de inicio de sesión! ¡Qué bien! Y si introducimos nuestras credenciales e iniciamos sesión... se nos redirige a la página de inicio en lugar de a la página del carrito. ¡Arreglemos eso también! En `lemon-squeezy_controller.js`, después de `response.url`, añade una cadena `?_target_path=`, y concatena `window.location.pathname`.

Para que esto funcione de verdad, abre `src/Security/LoginFormAuthenticator`, y al principio del método `onAuthenticationSuccess()`, añade `if ($targetPath = $request->query->get())`, pasando `_target_path` de los parámetros de consulta. Por último, `return new RedirectResponse($targetPath)`.

Esta vez, si cerramos la sesión e intentamos volver a registrarnos... seremos redirigidos a la página de inicio de sesión. Si volvemos a iniciar sesión... ¡boom! ¡Volvemos a la página del carrito! Haz clic en el botón de pago y... ¡se cargará nuestra impresionante superposición de pago! Rellenaré algunos datos para completar la compra... pulsa el botón "Pagar" y... ¡tachán! Aquí está nuestro mensaje de éxito! Siguiente: Aprendamos a escuchar los eventos JavaScript de LemonSqueezy y a utilizarlos* para sincronizar el ID de cliente con el usuario actual de forma alternativa.
