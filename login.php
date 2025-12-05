<?php
set_time_limit(300);
session_start();
include 'koneksi.php';
$msg = "";
$cardClass = ""; // default class card

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!ctype_digit($username)) {
        $msg = "Username hanya boleh angka!";
        $cardClass = "error";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND password=?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();

        if ($user) {
            $_SESSION['login'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['level'] = $user['level'];
            $cardClass = "success";

            // 🕒 Simpan waktu login ke database
            $conn->query("UPDATE users SET waktu_login = NOW() WHERE username = '$username'");

            // delay 1 detik biar kelihatan efek hijau
            echo "<script>
                setTimeout(function(){
                    window.location.href='" . ($user['level'] == 'admin' ? 'dashboard_admin.php' : 'dashboard_user.php') . "';
                }, 1000);
            </script>";
        } else {
            $msg = "Username atau password salah!";
            $cardClass = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - BRI Kanwil Pekanbaru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #007bff, #00aaff);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }

        .card {
            padding: 35px;
            border-radius: 15px;
            width: 360px;
            background: white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.4s ease;
        }

        .card.error {
            background: #ffcccc;
            border: 2px solid #ff0000;
        }

        .card.success {
            background: #ccffcc;
            border: 2px solid #00aa00;
        }

        h3 {
            text-align: center;
            margin-bottom: 25px;
            color: #007bff;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: move;
        }

        .input-group-text {
            background: #f1f1f1;
            border-radius: 10px 0 0 10px;
            border: none;
        }

        input.form-control {
            border-radius: 0 10px 10px 0;
            padding: 10px;
        }

        .btn-primary {
            background: #007bff;
            border: none;
            width: 100%;
            border-radius: 50px;
            transition: background 0.3s, transform 0.2s;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: scale(1.05);
        }

        a {
            text-decoration: none;
            color: #007bff;
        }

        a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 10px;
        }

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
        }

        .position-relative {
            position: relative;
        }
    </style>
</head>

<body>
    <div class="card <?php echo $cardClass; ?>" id="loginCard">
        <h3 id="cardHeader">Login BRI Kanwil Pekanbaru</h3>
        <?php if ($msg != "") echo "<div class='alert alert-danger'>$msg</div>"; ?>
        <form method="post">
            <div class="mb-3 input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Username PN" required>
            </div>
            <div class="mb-3 input-group position-relative">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Password" id="password" required>
                <span class="fas fa-eye password-toggle" id="togglePassword"></span>
            </div>
            <button type="submit" name="login" class="btn btn-primary">Login</button>
            <p class="mt-2 text-center">Belum punya akun? <a href="register.php">Register</a></p>
        </form>
    </div>

    <script>
        // Toggle Password
        document.querySelector('#togglePassword').addEventListener('click', function() {
            const password = document.querySelector('#password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Filter Username hanya angka
        document.querySelector("input[name='username']").addEventListener("input", function(e) {
            this.value = this.value.replace(/[^0-9]/g, "");
        });

        // Drag Card hanya via header
        function dragElement(elmnt, handle) {
            let pos1 = 0,
                pos2 = 0,
                pos3 = 0,
                pos4 = 0;
            if (handle) {
                handle.onmousedown = dragMouseDown;
            }

            function dragMouseDown(e) {
                e.preventDefault();
                pos3 = e.clientX;
                pos4 = e.clientY;
                document.onmouseup = closeDragElement;
                document.onmousemove = elementDrag;
            }

            function elementDrag(e) {
                e.preventDefault();
                pos1 = pos3 - e.clientX;
                pos2 = pos4 - e.clientY;
                pos3 = e.clientX;
                pos4 = e.clientY;
                elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
                elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
            }

            function closeDragElement() {
                document.onmouseup = null;
                document.onmousemove = null;
            }
        }
        dragElement(document.getElementById("loginCard"), document.getElementById("cardHeader"));
    </script>
</body>

</html>