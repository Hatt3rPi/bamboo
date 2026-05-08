/* Bamboo legacy helpers — funciones globales que sobreviven del header2.php viejo. */

function crear_poliza_web() {
  $.redirect('/bamboo/creacion_propuesta_poliza.php', {
    'accion': 'crear_poliza_web'
  }, 'post');
}
