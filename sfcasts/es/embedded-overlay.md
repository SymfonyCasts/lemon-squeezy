# Incrustar la superposición de pago LemonSqueezy

En el último capítulo, nos ensuciamos las manos y construimos un controlador Stimulus personalizado de LemonSqueezy. Muestra la página de pago de LemonSqueezy en un iFrame directamente en nuestro sitio. Eso está muy bien, pero ¿y si te dijera que podemos hacerlo aún mejor? Vamos a colocar la página de pago sobre la página de nuestro carrito y crear una superposición real, como un modal.

Pero antes de lanzarnos, vamos a añadir un par de funciones clave al botón "Pagar con LemonSqueezy":
* Evitar los dobles clics
* Y mostrar el progreso de carga

Si tenías curiosidad por saber por qué hemos creado un controlador Stimulus personalizado para el enlace de pago, ¡ésta es la razón!

## Evitar los dobles clics y mostrar el progreso de carga

Para empezar, abre `lemon-squeezy_controller.js`. Vamos a crear algunos métodos privados aquí. Empieza con `#disableLink()` y pásale un argumento `link`. Después añade `#enableLink()` y pasa también `link` como argumento.

Para `#disableLink()`, escribiremos algo de código para añadir la clase CSS `disabled` al enlace, desactivar los eventos de puntero y atenuar ligeramente el enlace. Escribe`link.classList.add('disabled')`, luego `link.style.pointerEvents = 'none'`, y termina con `link.style.opacity = '0.5'`.

En `#enableLink()`, haz lo contrario:`link.classList.remove('disabled')`, `link.style.pointerEvents = 'auto'`, y`link.style.opacity = '1'`.

Bien, en el método `openOverlay()`, justo después de crear `linkEl`, llama a `this.#disableLink(linkEl)`. Ahora, en el segundo `.then()` después de esta línea `window.LemonSqueezy...`, llama a `this.#enableLink(linkEl)`. Haz lo mismo en `.catch()` después de`console.error()`.

Muy bien, en nuestro sitio, recarga la página del carrito, y si hacemos clic varias veces en el botón "Pagar con LemonSqueezy"... podemos ver que está ligeramente atenuado e ignora por completo nuestros dobles clics. ¡Qué bien!

## Integrar la página de pago

Ahora, la parte divertida: ¡incrustar! Abre `src/Store/LemonSqueezyApi.php` y, en el método `createCheckoutUrl()`, después de configurar el ID de usuario personalizado, añade`$attributes['checkout_options']['embed'] = true`.

Actualiza la página del carrito, haz clic de nuevo en el botón de pago y... ¡ahí está: una nueva y brillante superposición de LemonSqueezy! Todavía podemos ver nuestra página del carrito en segundo plano. Cuando la cerremos, nuestro botón "Pagar con LemonSqueezy" estará listo de nuevo.

## Mejorando `createCheckoutUrl()`

De momento, estamos llamando a `createCheckoutUrl()` en un par de sitios: en`OrderController::createCheckout()` y de nuevo en `checkout()`. Si queremos utilizar la incrustación sólo para la versión JavaScript, podemos añadir un argumento booleano`$embed` a `LemonSqueezyApi::createCheckoutUrl()` que por defecto sea `false`. Sustituye también el código `true` que utilizamos antes por la nueva variable `$embed`. De vuelta en `OrderController`, pasa `true` a`createCheckoutUrl()` en la acción `createCheckout()`.

## Automatizar la inclusión de `lemon.js` 

Para asegurarnos de que nuestro controlador LemonSqueezy Stimulus funciona, tenemos que incluir`lemon.js` en cada página que lo utilicemos. Si eso suena tedioso, lo es, así que vamos a automatizarlo.

En el método `connect()`, crea una variable `script` igual a`window.document.querySelector('script[src=""]'`, copia la URL `lemon.js`de nuestra plantilla y pégala aquí. Comprueba que `script` no existe ya con `if (!script)`. Dentro, escribe `script = window.document.createElement('script')`. Ahora, configura`script.src` con la URL completa de `lemon.js`. Añade `script.defer = true` y, por último, añádelo al DOM con `window.document.head.appendChild(script)`.

¡Ahora podemos celebrarlo eliminando el bloque `javascripts` de la plantilla!

Recarga la página del carrito, haz clic en el botón de pago, podemos ver que se está cargando, y... ¡sí! ¡Todavía podemos ver la superposición!

## Depuración para usuarios no autenticados

Pero hay un problema para los usuarios no autenticados. Si cerramos la sesión, añadimos un producto al carrito e intentamos pagar de nuevo... no ocurre nada. Si abres las Herramientas de desarrollo, verás que la petición se redirige primero a una página de inicio de sesión, pero nuestra lógica JavaScript no sigue esa redirección. ¡Vamos a arreglarlo!

En nuestro código, añade un `console.log(response)` antes de la comprobación `response.ok`. De vuelta en nuestro sitio, en la pestaña "Consola", podemos ver que `response.redirected` se establece en `true` para esa petición. Añadamos otra comprobación -`if (response.redirected)` - y enviemos al usuario a la página de inicio de sesión con `window.location.href = response.url`. A continuación, detén la ejecución posterior de esta cadena con `return Promise.reject('User is not authenticated!')`. Añadiré un comentario más arriba para explicarlo.

## Redirigir a los usuarios de vuelta a la página del carrito

Vale, ahora si hacemos clic en el botón de compra cuando no hemos iniciado sesión... ¡somos redirigidos a la página de inicio de sesión! ¡Qué bien! Y si introducimos nuestras credenciales e iniciamos sesión... se nos redirige a la página de inicio en lugar de a la página del carrito.

¡Arreglemos eso también! En `lemon-squeezy_controller.js`, después de `response.url`, concatena`?_target_path=`, y `window.location.pathname`. Este parámetro de consulta `_target_path`es una convención de Symfony para indicarnos dónde redirigir después del inicio de sesión.

Tenemos un autenticador personalizado para nuestro formulario de inicio de sesión, así que para que esto funcione, tenemos que hacer algunos ajustes. Abre `src/Security/LoginFormAuthenticator`. Al principio del método `onAuthenticationSuccess()`, añade`if ($targetPath = $request->query->get('_target_path'))`. Dentro,`return new RedirectResponse($targetPath)`.

Esta vez, si cerramos la sesión e intentamos volver a entrar... seremos redirigidos a la página de inicio de sesión. Si volvemos a iniciar sesión... ¡boom! ¡Volvemos a la página del carrito! Haz clic en el botón de pago y... ¡se cargará nuestra impresionante ventana superpuesta de pago! Rellenaré algunos datos para que podamos completar el pago... pulsa el botón "Pagar", y... ¡tachán! ¡Aquí está nuestro mensaje de éxito!

A continuación: Vamos a aprender a escuchar los eventos JavaScript de LemonSqueezy y a utilizarlos para sincronizar el ID de cliente con el usuario actual como alternativa al webhook que configuramos antes.
