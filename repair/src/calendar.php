<?php
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ปฏิทินรายการแจ้งปัญหา</title>
  <!-- FullCalendar -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="style.css">
  <style>
    #calendar {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
    }

    .modal {
      z-index: 1055;
    }
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="container mt-5">
  <h3 class="mb-4">ปฏิทินรายการแจ้งปัญหา</h3>
  <div id='calendar'></div>
</div>

<!-- ✅ Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventModalLabel">ตรวจสอบรายการแจ้งซ่อม</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">ชื่อและนามสกุลผู้แจ้ง</label>
          <input type="text" class="form-control" id="modalReporter" readonly>
        </div>
        <div class="mb-2">
          <label class="form-label">เลขที่อ้างอิง</label>
          <input type="text" class="form-control" id="modalRef" readonly>
        </div>
        <div class="mb-2">
          <label class="form-label">สถานะ</label>
          <input type="text" class="form-control" id="modalStatus" readonly>
        </div>
        <div class="mb-2">
          <label class="form-label">สถานที่ตั้ง</label>
          <input type="text" class="form-control" id="modalLocation" readonly>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>

<!-- JS: FullCalendar + Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var calendarEl = document.getElementById('calendar');
  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'th',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
    },
    events: 'events.php',
    eventClick: function (info) {
      // รับข้อมูลจาก extendedProps
      document.getElementById('modalReporter').value = info.event.extendedProps.reporter;
      document.getElementById('modalRef').value = info.event.extendedProps.repair_id;
      document.getElementById('modalStatus').value = info.event.extendedProps.status;
      // document.getElementById('modalType').value = info.event.extendedProps.type; // ลบประเภทของงานออก
      document.getElementById('modalLocation').value = info.event.extendedProps.location;

      // เปิด Modal
      var modal = new bootstrap.Modal(document.getElementById('eventModal'));
      modal.show();
    }
  });
  calendar.render();
});
</script>
</body>
</html>
 