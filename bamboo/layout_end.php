    </main>
  </div>
</div>

<!-- jQuery + Bootstrap JS (mantenidos) -->
<script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>

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

</body>
</html>
