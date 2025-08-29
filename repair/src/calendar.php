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
    .modal { z-index: 1055; }
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="container mt-5">
  <h3 class="mb-4">ปฏิทินรายการแจ้งปัญหา</h3>
  <div id='calendar'></div>
</div>

<!-- Modal -->
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
          <!-- ถ้าต้องการ badge สี ให้เพิ่ม div นี้แทน input: <div id="modalStatusBadge"></div> -->
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
  // แม็พสถานะ → ชื่อไทย + สี (รองรับกำลังซ่อมโดย IT)
  const STATUS_MAP = {
    received:               { th: 'รับเรื่อง',                         color: 'info' },
    evaluate_it:            { th: 'ประเมิน (โดย IT)',                  color: 'warning' },
    evaluate_repairable:    { th: 'ประเมิน: ซ่อมได้โดย IT',            color: 'success' },
    in_progress:            { th: 'กำลังซ่อมโดย IT',                    color: 'primary' }, // ใหม่
    evaluate_external:      { th: 'ประเมิน: ซ่อมไม่ได้ - ส่งซ่อมภายนอก', color: 'danger' },
    evaluate_disposal:      { th: 'ประเมิน: อุปกรณ์ไม่คุ้มซ่อม/รอจำหน่าย', color: 'dark' },
    external_repair:        { th: 'ซ่อมไม่ได้ - ส่งซ่อมภายนอก',        color: 'danger' },
    procurement_managing:   { th: 'พัสดุจัดการส่งซ่อม',                 color: 'info' },
    procurement_returned:   { th: 'พัสดุซ่อมเสร็จส่งคืน IT',            color: 'success' },
    repair_completed:       { th: 'ซ่อมเสร็จ',                           color: 'success' },
    waiting_delivery:       { th: 'รอส่งมอบ',                            color: 'warning' },
    delivered:              { th: 'ส่งมอบ',                               color: 'success' },
    cancelled:              { th: 'ยกเลิก',                               color: 'secondary' },
    // legacy / fallback
    pending:                { th: 'รอดำเนินการ',                          color: 'secondary' },
    done:                   { th: 'ซ่อมเสร็จ',                             color: 'success' },
    '':                     { th: 'รอดำเนินการ',                          color: 'secondary' },
  };
  const toThaiStatus = (code) => STATUS_MAP[code] || { th: (code || 'ไม่ระบุสถานะ'), color: 'secondary' };

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
      // ดึงค่า
      const reporter = info.event.extendedProps.reporter || '-';
      const refId    = info.event.extendedProps.repair_id || info.event.id || '-';
      const statusCd = (info.event.extendedProps.status || '').trim();
      const location = info.event.extendedProps.location || '-';

      // แปลงสถานะเป็นชื่อไทย
      const { th: statusTh/*, color*/ } = toThaiStatus(statusCd);

      // เติมลงโมดัล
      document.getElementById('modalReporter').value = reporter;
      document.getElementById('modalRef').value      = refId;
      document.getElementById('modalStatus').value   = statusTh;
      document.getElementById('modalLocation').value = location;

      // ถ้าต้องการ badge สี แทน input:
      // document.getElementById('modalStatusBadge').innerHTML = `<span class="badge bg-${color}">${statusTh}</span>`;

      new bootstrap.Modal(document.getElementById('eventModal')).show();
    }
  });
  calendar.render();
});
</script>
</body>
</html>
