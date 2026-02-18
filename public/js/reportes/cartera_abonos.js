// Inicialización de DataTable para Cartera Abonos
$(document).ready(function() {
  // Si ya está definido en la vista, este script puede ser mínimo o eliminarse
  if ($('#report-table').length) {
    $('#report-table').DataTable({
      // Configuración precargada en la vista; se puede extender aquí si se desea
    });
  }
});
