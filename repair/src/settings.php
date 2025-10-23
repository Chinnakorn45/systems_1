<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ตั้งค่าระบบ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
      .love-text {
        font-size: 1.75rem;
        font-weight: 700;
        color: #e53935;
        letter-spacing: .5px;
        text-transform: uppercase;
        text-shadow: 1px 1px 3px rgba(0,0,0,.08);
        animation: pulse 1.5s infinite;
      }
      @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.03); }
        100% { transform: scale(1); }
      }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  </head>
  <body>
    <?php include 'sidebar.php'; ?>
    <div class="container mt-5">
      <h3 class="mb-4"><i class="fas fa-cog me-2"></i> ตั้งค่าระบบ</h3>
      <div class="card">
        <div class="card-body text-center">
          <?php
            $message = "I love my Job";
            echo "<div class='love-text'>" . $message . "</div>";
          ?>
        </div>
      </div>
    </div>
    <?php include 'toast.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
