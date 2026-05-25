# Reunión Adriana — Setup Brevo y planificación (2026-05-15)

Participantes: Felipe Abarca, Adriana Sandoval.
Duración: ~31 min. Mayoritariamente operativa, sin discusión de flujo siniestros.
Transcripción origen: `Bamboo Transcripción (3).txt` (15-may-2026).
Próxima reunión: **lunes 18-may-2026, 10:00 AM**.

## Síntesis ejecutiva

Reunión de configuración técnica más que de diseño:

1. **Adriana reenvió código NIC.cl** (el de la sesión anterior había expirado).
2. **Setup de Brevo** desde la cuenta de Adriana: verificación del sender,
   intentar autenticar el dominio (se vio bloqueado por el problema DNS ya
   conocido — los DNS records nuevos no propagan en Planet Hosting).
3. **Login a Planet Hosting** (contraseña: `RRusia2017` con doble R mayúscula).
4. **Plan Brevo confirmado**: Free (500/mes, 300/día). Si escala a más de 5k/mes
   → próximo plan ~$10 USD/mes (Adriana lo encuentra aceptable).
5. **Adriana confirma**: tiene **~400 clientes**, no 300 ni 2.000.
6. **Felipe reconoce el problema de su IP dinámica** rebotando con Planet
   Hosting cada cierto tiempo: *"este tipo de internet renueva la IP cada
   ciertos minutos y esta página no lo soporta, entonces me tira para atrás."*
7. **Acuerdo para el lunes 18-may**: Felipe presenta una propuesta más
   funcional. Foco: **revisar el flujo de NO-vehículo** (incendio, etc.) que
   "es la que más le va a importar a Adriana".

## Decisiones nuevas

### Brevo

- **Plan**: Free (mientras no se llegue a 5k correos/mes).
- **Cuenta**: de Adriana (`asandoval@bambooseguros.cl` como dueña).
- **Sender objetivo**: `notificaciones@bambooseguros.cl` (pendiente de que
  propague DNS para autenticarlo).
- Si crece: $10 USD/mes el primer plan paga.

### Escala estimada

- ~400 clientes activos.
- Envíos típicos diarios: bajos (cadena de siniestros, ~1-5 correos por
  siniestro).
- Campañas masivas ocasionales (ej. SOAP).
- Plan Free suficiente para operación normal.

### Próxima reunión: lunes 18-may 10:00

**Agenda acordada:**
1. Revisar **el flujo vehículo** (ya está implementado — repaso rápido para que
   Adriana lo vea funcionando).
2. **Revisar el flujo no-vehículo** (incendio, daños materiales, etc.) — Adriana
   dijo: *"al final va a ser la que más me va a importar"*. Hasta ahora se ha
   hablado menos de él, hay que validar pieza por pieza.

## Bloqueos actuales (conocidos)

- **DNS de bambooseguros.cl**: los 4 records de Brevo (DKIM 1, DKIM 2, brevo-code,
  DMARC) están en el panel cPanel pero **el cluster DNS de Planet Hosting no
  los replica**. Diagnóstico confirmado vía API el 17-may: serial SOA en el
  master (`2026051801`) vs en los slaves (`2026040701`). **Ticket abierto a
  Planet Hosting** el 17-may para que ejecuten `rndc reload` / sync zone.
- Hasta que destraben, Brevo no puede autenticar el dominio → los correos
  automáticos del flujo de siniestros no podrán enviarse desde
  `notificaciones@bambooseguros.cl`. Como fallback temporal podríamos usar
  `asandoval@bambooseguros.cl` (sender ya verificado individualmente).

## Pendientes para la reunión del lunes

1. **Tener listo el repaso del flujo vehículo** funcionando en QA.
2. **Tener planteado el flujo no-vehículo** con los puntos abiertos:
   - Diferencias entre incendio, daños materiales, robo no-vehículo, RC.
   - Cómo se sigue el seguimiento post-finiquito hasta el pago al cliente.
   - Adriana iba a averiguar el tema del **finiquito en vehículos** (transcripción
     11-may). Si trae info, modelamos.
3. (Si Planet Hosting destraba el DNS antes del lunes) Mostrarle que los correos
   automáticos ya están funcionando.
