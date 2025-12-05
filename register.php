<?php
session_start();
include 'koneksi.php';
$msg = "";
$cardClass = ""; // class untuk border

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $level = 'user';

    // Validasi: username hanya angka
    if (!ctype_digit($username)) {
        $msg = "Username hanya boleh angka!";
        $cardClass = "error";
    } else {
        // cek username sudah ada?
        $check = $conn->prepare("SELECT * FROM users WHERE username=?");
        $check->bind_param("s", $username);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $msg = "Username sudah terdaftar!";
            $cardClass = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, level) VALUES (?,?,?)");
            $stmt->bind_param("sss", $username, $password, $level);
            if ($stmt->execute()) {
                $msg = "Registrasi sukses! Silahkan login.";
                $cardClass = "success";
            } else {
                $msg = "Registrasi gagal!";
                $cardClass = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register - BRI Kanwil Pekanbaru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #007bff;
            /* biru tetap */
            font-family: Arial, sans-serif;
            overflow: hidden;
        }

        .card {
            padding: 35px;
            border-radius: 15px;
            width: 360px;
            background: white;
            /* card tetap putih */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            position: absolute;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            /* default */
        }

        .card.error {
            border-color: #ff0000;
        }

        .card.success {
            border-color: #00aa00;
        }

        h3 {
            text-align: center;
            margin-bottom: 25px;
            color: #007bff;
            /* header terlihat jelas di card putih */
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: move;
            /* drag hanya dari header */
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
            background: #0056b3;
            border: none;
            width: 100%;
            border-radius: 50px;
            transition: background 0.3s, transform 0.2s;
        }

        .btn-primary:hover {
            background: #003d80;
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
    <div class="card <?php echo $cardClass; ?>" id="registerCard">
        <h3 id="cardHeader">Register User</h3>
        <?php if ($msg != "") echo "<div class='alert alert-info'>$msg</div>"; ?>
        <form method="post">
            <div class="mb-3 input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Username (PN)" required>
            </div>
            <div class="mb-3 input-group position-relative">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Password" id="password" required>
                <span class="fas fa-eye password-toggle" id="togglePassword"></span>
            </div>
            <button type="submit" name="register" class="btn btn-primary">Register</button>
            <p class="mt-2 text-center">Sudah punya akun? <a href="login.php">Login</a></p>
        </form>
    </div>

    <script>
        // Toggle Password
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Filter username input: hanya angka
        document.querySelector("input[name='username']").addEventListener("input", function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Set posisi awal card tengah layar
        window.onload = function() {
            const card = document.getElementById("registerCard");
            card.style.top = (window.innerHeight / 2 - card.offsetHeight / 2) + "px";
            card.style.left = (window.innerWidth / 2 - card.offsetWidth / 2) + "px";
        };

        // Drag Card hanya via header
        function dragElement(elmnt, handle) {
            let pos1 = 0,
                pos2 = 0,
                pos3 = 0,
                pos4 = 0;
            handle.onmousedown = dragMouseDown;

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
        dragElement(document.getElementById("registerCard"), document.getElementById("cardHeader"));
    </script>
</body>

</html>