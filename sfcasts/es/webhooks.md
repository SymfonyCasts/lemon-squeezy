# Escuchar Webhooks

## Asignar un cliente de LemonSqueezy a un usuario

Así que... nuestro sitio tiene usuarios, y LemonSqueezy tiene clientes... pero por lo que sabe nuestro código, son unos completos desconocidos el uno para el otro. Para construir cualquier integración significativa -como recuperar los pedidos de un usuario, depurar problemas o simplemente tener una experiencia de soporte más fluida cuando los clientes se ponen en contacto con nosotros por sus pedidos- necesitamos conectarlos. Más concretamente, queremos almacenar el ID de cliente de LemonSqueezy en la entidad correspondiente de `User`.

Pero aquí hay un giro: Cuando alguien termina de pagar, LemonSqueezy crea un "cliente" para nosotros entre bastidores. No puedes crear previamente un ID de cliente ni pasar uno existente durante el pago. ¡No! LemonSqueezy le asigna uno en función de la dirección de correo electrónico que haya introducido y de si ha iniciado sesión en su cuenta de LemonSqueezy.

## Webhooks

Entonces... ¿cómo podemos coger ese ID de cliente y vincularlo a nuestro usuario? ¿Manualmente? Bueno, sí... ¿pero quién quiere hacer eso? Yo no. Vamos a automatizarlo, utilizando unos prácticos webhooks.

Podemos enviar algunos metadatos cuando se cree el Pedido, como el ID de usuario de nuestro sistema, y cuando recibamos el webhook (hablaremos de ello más adelante), leeremos esos metadatos, buscaremos al usuario adecuado en nuestra base de datos y almacenaremos el ID de cliente con él.

Si dijeras "Espera... ¿No podemos obtener también el ID de cliente justo después de que pasen por caja?" ¡es correcto! LemonSqueezy también envía un evento JavaScript `checkout:complete`en el momento del pago que incluye el ID de cliente, lo que podría ser genial para el desarrollo local cuando no quieres configurar túneles webhook.

Así que... ¡hagamos las dos cosas!

## Componente Webhook de Symfony

Como los webhooks son una solución flexible y robusta para producción, empecemos por ahí. En la página principal de LemonSqueezy, ve a "Recursos", elige "Documentos de ayuda" y, aquí abajo, en la sección "Webhooks", haz clic en "Tipos de eventos".

Si nos desplazamos un poco... Sólo veo unos pocos eventos relacionados con pedidos. Para nuestros propósitos, parece que necesitaremos este evento `order_created`, que debería proporcionarnos los datos del pedido que buscamos. Si hacemos clic en él y nos desplazamos hacia abajo... ¡sí! devuelve `customer_id`! ¡Qué bien!

¡Ahora, podríamos crear un `WebhookController` para manejar esto nosotros mismos, pero Symfony nos cubre las espaldas y ha lanzado recientemente un nuevo componente Webhook que puede ayudarnos a manejar esto incluso mejor! ¡Vamos a probarlo! En tu terminal, instálalo con:

```terminal
composer require webhook
```

Esto nos proporciona algunas dependencias muy necesarias. MakerBundle también tiene un comando para ayudarnos a empezar. Ejecuta: :

```terminal
bin/console make:webhook
```

Lo llamaremos `lemon-squeezy`. Para el emparejador, elijamos:`PathRequestMatcher`, que es la opción 6, y luego `MethodRequestMatcher` - opción 5. Y como LemonSqueezy nos envía los datos en formato JSON, seleccionemos también 4 -`IsJsonRequestMatcher`. Pulsa enter una vez más, y... ¡bien! Esto generó un analizador sintáctico y un consumidor -más sobre ellos en un momento- y también creó una nueva ruta. Si ejecutamos:

```terminal
bin/console debug:router | grep webhook
```

... ahí está: ¡nuestra nueva URL `/webhook/{type}`! El `{type}` debe ser el nombre del webhook`lemon-squeezy` que establecimos antes. Se trata de una URL específica que gestionará los webhooks de LemonSqueezy. Si la abrimos en el navegador... ¡sí! Se produce un error`RejectWebhookException`:

> La petición no coincide.

¡Vamos a configurarlo! Copia esta URL y, de vuelta en el panel de control de LemonSqueezy, en "Configuración", "Webhooks", haz clic en el signo más para editar el webhook. En el campo "URL de devolución de llamada", pega la URL que hemos copiado hace un momento. Pero... hm... puede que ya hayas detectado el problema aquí. Esta URL no es pública, por lo que LemonSqueezy no puede acceder a nuestro host local.

Tenemos que configurar correctamente nuestros webhooks con una herramienta como Ngrok, para que LemonSqueezy pueda hablar con nuestra máquina local. Eso a continuación.
