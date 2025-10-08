<?php
// Shared SweetAlert2 toast utilities for repair pages
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Toast helpers using SweetAlert2
function showToast({icon='success', title='', timer=2200} = {}){
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon,
    title,
    showConfirmButton: false,
    timer,
    timerProgressBar: true
  });
}

// Map status code -> Thai label for toasts
const STATUS_LABEL = {
  pending: 'รอดำเนินการ',
  received: 'รับเรื่องแล้ว',
  evaluate_it: 'ส่งให้ IT ประเมิน',
  evaluate_repairable: 'ประเมิน: ซ่อมได้โดย IT',
  in_progress: 'กำลังซ่อมโดย IT',
  evaluate_external: 'ประเมิน: ส่งซ่อมภายนอก',
  evaluate_disposal: 'ประเมิน: ไม่คุ้มซ่อม/รอจำหน่าย',
  external_repair: 'ส่งซ่อมภายนอกแล้ว',
  procurement_managing: 'พัสดุดำเนินการส่งซ่อม',
  procurement_returned: 'ได้รับคืนจากการซ่อม (พัสดุ)',
  repair_completed: 'ซ่อมเสร็จแล้ว',
  waiting_delivery: 'รอส่งมอบ',
  delivered: 'ส่งมอบเรียบร้อย',
  cancelled: 'ยกเลิกรายการแล้ว'
};

document.addEventListener('DOMContentLoaded', function(){
  const url = new URL(window.location.href);

  // Standardized success after status update
  const updated = url.searchParams.get('status_updated');
  const updatedStatus = url.searchParams.get('updated_status');
  if (updated === '1'){
    const label = STATUS_LABEL[updatedStatus] || 'อัปเดตข้อมูลสำเร็จ';
    let icon = 'success';
    if (updatedStatus === 'cancelled') icon = 'warning';
    if (updatedStatus === 'evaluate_external' || updatedStatus === 'external_repair') icon = 'info';
    showToast({ icon, title: label });
  }

  // Generic flash via query params
  const qSuccess = url.searchParams.get('flash_success');
  const qInfo    = url.searchParams.get('flash_info');
  const qWarn    = url.searchParams.get('flash_warning');
  const qError   = url.searchParams.get('flash_error');
  if (qSuccess) showToast({ icon: 'success', title: qSuccess });
  if (qInfo)    showToast({ icon: 'info',    title: qInfo    });
  if (qWarn)    showToast({ icon: 'warning', title: qWarn    });
  if (qError)   Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: qError });

  // Server-provided messages (optional)
  <?php if (isset($error) && !empty($error)): ?>
  Swal.fire({ icon: 'error', title: 'ไม่สามารถดำเนินการได้', text: <?= json_encode($error, JSON_UNESCAPED_UNICODE) ?> });
  <?php endif; ?>
  <?php if (isset($success) && !empty($success)): ?>
  showToast({ icon: 'success', title: <?= json_encode($success, JSON_UNESCAPED_UNICODE) ?> });
  <?php endif; ?>
  <?php if (isset($info) && !empty($info)): ?>
  showToast({ icon: 'info', title: <?= json_encode($info, JSON_UNESCAPED_UNICODE) ?> });
  <?php endif; ?>
  <?php if (isset($warning) && !empty($warning)): ?>
  showToast({ icon: 'warning', title: <?= json_encode($warning, JSON_UNESCAPED_UNICODE) ?> });
  <?php endif; ?>

  
});
</script>

