
<section class="footer">
    <div class="container-fluid fixed-bottom">
        <div class="row">
            <div class="col-12" style="padding: 8px 0 0 0;">
                <h5 class="d-flex justify-content-center align-items-center text-white">Tetra Technologies v 1.0</h5>
            </div>
        </div>
    </div>
</section>




   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

!-- External JavaScript and CDN links -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#admindatatable').DataTable();

    // Custom filtering function that works on date range
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var date = data[4]; // The Date/Time column index in your table

            // Only filter when both dates are selected
            if (startDate && endDate) {
                var dateMoment = moment(date, 'YYYY-MM-DD HH:mm:ss');
                var startMoment = moment(startDate);
                var endMoment = moment(endDate);

                return dateMoment.isBetween(startMoment, endMoment, undefined, '[]');
            }
            return true; // Return all records if dates are not selected
        }
    );

    // Trigger the table filtering when the dates are changed
    $('#startDate, #endDate').on('change', function() {
        table.draw();
    });
});
</script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
</body>
</html>