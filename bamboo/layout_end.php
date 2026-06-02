    </main>
  </div>
</div>

<!-- jQuery 3.5 + Bootstrap 4.6.2 (4.4.1 era incompatible con jQuery 3.5 —
     rompía collapse con 'Cannot convert object to primitive value') -->
<script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>

<!-- Helpers globales -->
<script src="https://cdn.jsdelivr.net/npm/js-cookie@rc/dist/js.cookie.min.js"></script>
<script src="/assets/js/jquery.redirect.js"></script>
<script src="/assets/js/bamboo/legacy.js"></script>

<!-- Sidebar collapse persistence -->
<script>
  (function () {
    var KEY = 'bb_sidebar_collapsed';
    var shell = document.getElementById('appShell');
    if (!shell) return;
    if (localStorage.getItem(KEY) === '1') shell.classList.add('collapsed');
    var toggle = document.querySelector('.bb-topbar .toggle');
    if (toggle) toggle.addEventListener('click', function () {
      localStorage.setItem(KEY, shell.classList.contains('collapsed') ? '1' : '0');
    });
  })();
</script>

<!-- UF / USD / fecha — fetch a mindicador.cl (replica del header viejo) -->
<script>
  (function () {
    function formateoNumero(x) {
      return x.toString().replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    var req = new XMLHttpRequest();
    req.open('GET', 'https://mindicador.cl/api', true);
    req.onload = function () {
      if (req.status < 200 || req.status >= 400) return;
      try {
        var data = JSON.parse(req.response);
        var d = new Date(data.fecha);
        var dd = String(d.getDate()).padStart(2, '0');
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var fecha = dd + '/' + mm + '/' + d.getFullYear();
        var uf = document.getElementById('bb-uf');
        var usd = document.getElementById('bb-usd');
        var fec = document.getElementById('bb-fecha');
        if (uf)  uf.textContent  = '$' + formateoNumero(data.uf.valor);
        if (usd) usd.textContent = '$' + formateoNumero(data.dolar.valor);
        if (fec) fec.textContent = fecha;
      } catch (e) { /* silencioso */ }
    };
    req.send();
  })();
</script>

</body>
</html>
