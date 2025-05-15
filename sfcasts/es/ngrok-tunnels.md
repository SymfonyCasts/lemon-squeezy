# Túneles Ngrok

En el capítulo anterior, nos ensuciamos las manos con el componente Webhook de Symfony. Creamos una ruta única para gestionar nuestros webhooks, pero... hay una pega: LemonSqueezy no puede acceder directamente a nuestra máquina local. Parece un problema, ¿verdad? ¡No te preocupes! ¡Tenemos una solución! Vamos a utilizar una herramienta inteligente llamada "Ngrok" para crear un túnel a nuestra máquina local. Eso nos dará una URL pública que reenviará todas las peticiones a nuestra máquina local. Es muy fácil de usar y, aunque el plan gratuito tiene algunas limitaciones, es más que suficiente para empezar. ¡Manos a la obra!

## Instalar Ngrok

Lo primero es lo primero: Instala Ngrok en tu sistema operativo desde el sitio web oficial. Yo uso un Mac, así que lo instalaré utilizando el gestor de paquetes Homebrew. En realidad, ya lo tengo instalado, así que me saltaré este paso y lo iniciaré desde mi terminal. Cuando estés listo, ejecuta:

```terminal
ngrok http 8000
```

Como la dirección de nuestro sitio web local utiliza 127.0.0.1:8000, es crucial que utilicemos el puerto 8000 aquí. Si utilizas un puerto diferente, utiliza ese en su lugar.

## Configurar Ngrok

Después de ejecutar esto, aquí abajo, verás esta línea de "Reenvío" con una URL aleatoria. Eso es lo que está reenviando peticiones a nuestro http://localhost:8000. Estoy en un Mac, así que mantendré pulsado `Cmd` y haré clic en la nueva URL para abrirla, y... ¡tachán! ¡Estamos en nuestro sitio web! Está vivito y coleando, y ahora tiene una URL pública. ¡Qué locura!

El componente Webhook añadió una nueva ruta `/webhook`, que puedes ver en la lista de rutas. Como hemos creado un webhook `lemon-squeezy`, se añade a la ruta como webhook "{tipo}". La URL final tendrá este aspecto

`https://0787-5-154-3-6.ngrok-free.app/webhook/lemon-squeezy`

Esto nos da un error, pero podemos ignorarlo por ahora, copiar la URL y pegarla en el campo "URL de devolución de llamada" de LemonSqueezy que vimos antes.

## Generar un secreto de firma

Bien, ahora necesitamos algo llamado "secreto de firma", que básicamente es una cadena aleatoria. Podríamos escribirla directamente en `webhook.yaml`, pero para mantener las cosas ordenadas, vamos a guardarla también como una variable de entorno. Utilizaré una mezcla de letras y números en distintos casos para mi secreto. Puedes utilizar cualquier herramienta de generación de contraseñas para obtener una cadena más aleatoria. Una vez hayas generado tu secreto, cópialo. Yo estoy de acuerdo con enviar esto al repositorio, así que abriré mi archivo `.env` y lo estableceré como `LEMON_SQUEEZY_SIGNING_SECRET`. Pero recuerda, esto es un secreto, ¡así que haz como si nunca hubieras visto el mío! Ahora, en el archivo `webhook.yaml`, establece el secreto como `%env(LEMON_SQUEEZY_SIGNING_SECRET)%` y enciérralo entre comillas simples.

## Seleccionar eventos y guardar el Webhook

De vuelta en el panel de control, selecciona el evento "order_created". De momento, es el único que nos interesa. Haz clic en "Guardar Webhook" y... ¡perfecto! ¡Nuestra configuración del webhook está lista!

## Utilizar la interfaz web de Ngrok

Si echas un vistazo al terminal donde ejecutamos el comando Ngrok, verás otra URL local para la "Interfaz Web". Mantendré pulsado `Cmd` y haré clic en la URL para abrirla en una pestaña nueva. ¡Bienvenido a la Interfaz Web de Ngrok! Aquí puedes ver todas las peticiones que llegan a tu URL pública. Esto es increíblemente útil para depurar y hacer un seguimiento de lo que ocurre con tus webhooks.

## Activar un webhook

Ahora que tenemos todo esto configurado, vamos a activar un webhook. Vuelve a nuestro sitio utilizando la URL local, selecciona una limonada (esta vez elegiré sandía) y añádela a nuestro carrito. Haz clic en el botón "Pagar"... rellena la información de pago, la dirección de facturación y, al final, haz clic en "Pagar" para completar la compra. ¡Éxito! LemonSqueezy debería habernos enviado ese webhook, así que vamos a comprobarlo.

## Depuración y gestión de problemas

De vuelta en el inspector web de Ngrok, notarás algunas peticiones fallidas. Y si esperamos un momento... veremos otra petición fallida. Se trata de la misma petición que LemonSqueezy intentó entregar y falló. Cada vez, devolvemos un código de estado `406 Not Acceptable`, por lo que LemonSqueezy piensa que no se ha procesado correctamente y vuelve a intentarlo. ¿Por qué ocurre esto?

Cada vez que nuestro servidor no responde con un código de estado correcto, LemonSqueezy intenta entregarlo de nuevo transcurridos 5 segundos, luego 25 segundos y, finalmente, 125 segundos. Si falla tres veces, LemonSqueezy tira la toalla porque no puede seguir intentando entregarlo eternamente. Eso significa que tenemos que reintentarlo manualmente desde el panel de control de LemonSqueezy.

## Para terminar

Ahora que LemonSqueezy puede chatear con nuestro sitio web local en tiempo real, estamos listos para implementar el manejo de un webhook real. Hagámoslo a continuación.
