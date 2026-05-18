# CartPinger — Guía de instalación y configuración

Tiempo estimado: 30–45 minutos (la mayor parte es esperar la verificación de Meta, no trabajo tuyo).

---

## Índice

1. [Requisitos previos](#1-requisitos-previos)
2. [Instalar el plugin](#2-instalar-el-plugin)
3. [Crear tu cuenta Meta Business](#3-crear-tu-cuenta-meta-business)
4. [Añadir un número de WhatsApp Business](#4-añadir-un-número-de-whatsapp-business)
5. [Obtener el Access Token y el Phone ID](#5-obtener-el-access-token-y-el-phone-id)
6. [Configurar CartPinger en WordPress](#6-configurar-cartpinger-en-wordpress)
7. [Activar los flujos](#7-activar-los-flujos)
8. [Verificar que funciona](#8-verificar-que-funciona)
9. [Preguntas frecuentes de instalación](#9-preguntas-frecuentes-de-instalación)

---

## 1. Requisitos previos

Antes de empezar, confirma que tienes:

- WordPress 6.2 o superior
- WooCommerce 7.0 o superior
- PHP 8.1 o superior (puedes verlo en WordPress → Herramientas → Salud del sitio)
- Un número de teléfono **que no esté ya registrado en WhatsApp** (puede ser un número de móvil nuevo, una línea VoIP, o el número fijo de tu empresa)
- Una cuenta en [Meta Business Manager](https://business.facebook.com) (gratuita)

> **Nota sobre el número:** Si tu número personal ya tiene WhatsApp instalado, no puedes usarlo directamente. La opción más sencilla es comprar una SIM prepago de bajo coste o usar un número VoIP (Twilio, Vonage, etc.).

---

## 2. Instalar el plugin

### Opción A — Desde WordPress.org (recomendada)

1. En tu panel de WordPress, ve a **Plugins → Añadir nuevo**.
2. Busca `CartPinger`.
3. Haz clic en **Instalar ahora** y luego en **Activar**.

### Opción B — Instalación manual

1. Descarga el ZIP del plugin desde [github.com/tfreire988/whatscom/releases](https://github.com/tfreire988/whatscom/releases).
2. En WordPress ve a **Plugins → Añadir nuevo → Subir plugin**.
3. Selecciona el ZIP descargado y haz clic en **Instalar ahora**.
4. Haz clic en **Activar plugin**.

Tras activar, verás **CartPinger** en el menú lateral de WordPress.

---

## 3. Crear tu cuenta Meta Business

Esta es la parte más larga, pero Meta la hace bastante guiada.

### 3.1 Crear el Business Manager

1. Ve a [business.facebook.com](https://business.facebook.com) e inicia sesión con tu cuenta de Facebook personal (necesitas una para crear el Business Manager).
2. Haz clic en **Crear cuenta**.
3. Rellena el nombre de tu empresa, tu nombre y tu email de empresa.
4. Confirma el email que te llegará.

### 3.2 Crear una app en Meta for Developers

1. Ve a [developers.facebook.com/apps](https://developers.facebook.com/apps).
2. Haz clic en **Crear app**.
3. Selecciona el tipo **Business**.
4. Dale un nombre (por ejemplo, `CartPinger TuTienda`) y selecciona tu Business Manager recién creado.
5. En el panel de la app, busca el producto **WhatsApp** y haz clic en **Configurar**.

### 3.3 Verificar tu empresa con Meta

Meta requiere verificar que tu empresa es real. La mayoría de tiendas legítimas son aprobadas en 1–3 días.

1. En Business Manager, ve a **Configuración → Centro de seguridad**.
2. Haz clic en **Iniciar verificación**.
3. Introduce los datos de tu empresa: nombre legal, dirección, sitio web y número de teléfono.
4. Meta te pedirá un documento acreditativo. Sirve cualquiera de estos:
   - Certificado de registro de la empresa (escritura de constitución, Modelo 036/037 en España)
   - Extracto bancario con el nombre de la empresa
   - Factura de suministros (luz, gas, teléfono) a nombre de la empresa
5. Sube el documento y envía la solicitud.

> **Tiempo de espera:** Meta tarda entre 1 y 7 días hábiles en verificar. Recibirás un email cuando esté listo. No necesitas hacer nada más hasta entonces.

---

## 4. Añadir un número de WhatsApp Business

Una vez verificada tu empresa:

1. En el panel de tu app en Meta for Developers, ve a **WhatsApp → Configuración**.
2. En la sección **Números de teléfono**, haz clic en **Añadir número de teléfono**.
3. Introduce el número que vas a usar (el que no tiene WhatsApp instalado).
4. Elige cómo recibir el código de verificación: **SMS** o **Llamada de voz**.
5. Introduce el código de 6 dígitos que recibirás.

Tu número ya está registrado como número de WhatsApp Business.

---

## 5. Obtener el Access Token y el Phone ID

CartPinger necesita dos datos para conectarse a la API de Meta:

### Access Token permanente

1. Ve a [business.facebook.com/settings/system-users](https://business.facebook.com/settings/system-users).
2. Haz clic en **Añadir** para crear un usuario del sistema. Nómbralo `cartpinger-bot` y asígnale rol **Empleado**.
3. Haz clic en **Generar token nuevo**.
4. Selecciona tu app de WhatsApp.
5. Activa el permiso `whatsapp_business_messaging`.
6. Haz clic en **Generar token** y **copia el token** que aparece — solo se muestra una vez.

> Guarda el token en un lugar seguro (un gestor de contraseñas como Bitwarden o 1Password). Si lo pierdes tendrás que generar uno nuevo.

### Phone Number ID

1. En Meta for Developers, ve a **WhatsApp → Configuración de la API**.
2. En la sección **De**, selecciona tu número de teléfono.
3. Copia el **ID de número de teléfono** que aparece debajo (es un número largo, ejemplo: `123456789012345`).

---

## 6. Configurar CartPinger en WordPress

1. En WordPress, ve a **CartPinger → Configuración**.
2. Rellena los campos:

| Campo | Valor |
|-------|-------|
| **Access Token** | El token que copiaste en el paso 5 |
| **Phone Number ID** | El ID de número del paso 5 |
| **Webhook Verify Token** | Escribe cualquier texto secreto (ej. `mi-secreto-123`). Lo necesitarás en el siguiente paso. |

3. Haz clic en **Guardar cambios**.

### Configurar el Webhook en Meta

Para recibir confirmaciones de entrega y lectura, Meta necesita saber la URL de tu tienda:

1. En Meta for Developers, ve a **WhatsApp → Configuración → Webhooks**.
2. Haz clic en **Editar**.
3. Introduce:
   - **URL de devolución de llamada:** `https://tutienda.com/?cartpinger_webhook=1`
   - **Token de verificación:** el mismo texto que pusiste en WordPress
4. Haz clic en **Verificar y guardar**.
5. Suscríbete al campo `messages`.

---

## 7. Activar los flujos

1. En WordPress, ve a **CartPinger → Flujos**.
2. Activa el toggle **Carrito abandonado** — CartPinger enviará automáticamente el WhatsApp 1 hora después de que un cliente abandone el carrito (si el cliente dio su consentimiento en el checkout).
3. Activa el toggle **Widget de chat** si quieres mostrar el botón flotante de WhatsApp en tu tienda.

---

## 8. Verificar que funciona

Para hacer una prueba rápida:

1. Abre tu tienda en modo incógnito.
2. Añade cualquier producto al carrito.
3. Ve al checkout e introduce un número de teléfono real (el tuyo) y marca el checkbox de WhatsApp.
4. **No completes el pedido** — cierra la pestaña.
5. Espera 1 hora (o reduce el tiempo en **CartPinger → Configuración → Tiempo de espera** a 1 minuto para la prueba).
6. Deberías recibir el WhatsApp en tu teléfono.

> En el panel **CartPinger → Logs** puedes ver el estado de todos los mensajes enviados: pendiente, enviado, entregado, leído o fallido.

---

## 9. Preguntas frecuentes de instalación

**El webhook no verifica / da error 403**
Asegúrate de que la URL de tu tienda es accesible desde internet (no vale `localhost`). Si tienes un plugin de seguridad como Wordfence, puede estar bloqueando la petición — añade la IP de Meta a la lista blanca o desactiva temporalmente el firewall para hacer la prueba.

**Meta me pide verificar la empresa pero no tengo documentos a nombre de empresa**
Si operas como autónomo, sirve el Modelo 036/037 de la Agencia Tributaria o cualquier factura oficial que incluya tu nombre y número de identificación fiscal.

**El mensaje llega pero el link de recuperación no funciona**
Comprueba que en **CartPinger → Configuración** la URL base de tu tienda es correcta (sin barra al final). Si usas HTTPS, asegúrate de que el certificado SSL está activo.

**¿Cuántas conversaciones gratuitas tengo?**
Meta regala 1.000 conversaciones de servicio al mes. Para carritos abandonados, la categoría es "marketing", que tiene un coste de aproximadamente €0,08 por conversación en España. Las primeras 1.000 conversaciones de marketing también son gratuitas al registrarte.

**No recibo el código de verificación del número de teléfono**
Prueba con la opción de llamada de voz en vez de SMS. Si el número es VoIP, algunos proveedores bloquean SMS de corto; la llamada de voz funciona mejor.

---

## Soporte

Si te atascas en algún paso, escríbeme directamente a [info@cartpinger.com](mailto:info@cartpinger.com) o abre un issue en [github.com/tfreire988/whatscom/issues](https://github.com/tfreire988/whatscom/issues).

Respondo en menos de 24 horas.
