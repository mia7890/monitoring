<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register | Monitoring System</title>
    <link rel="stylesheet" href="{{ asset_versioned('style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .register-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 50%, #fce7f3 100%);
            padding: 24px;
            font-family: 'Inter', sans-serif;
        }
        .register-shell {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 32px rgba(0,0,0,0.06), 0 2px 8px rgba(0,0,0,0.04);
            padding: 36px 32px;
            animation: slideUp 0.4s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .register-shell .logo-mark {
            text-align: center;
            margin-bottom: 8px;
        }
        .register-shell .logo-mark img {
            width: 48px;
            height: 48px;
            border-radius: 12px;
        }
        .register-shell .welcome-label {
            text-align: center;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #b52f32;
            text-transform: uppercase;
            margin: 0 0 4px 0;
        }
        .register-shell h1 {
            text-align: center;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
        }
        .register-shell .subtitle {
            text-align: center;
            font-size: 13px;
            color: #64748b;
            margin: 0 0 24px 0;
            line-height: 1.5;
        }
        .register-form .form-group {
            margin-bottom: 16px;
        }
        .register-form .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 5px;
        }
        .register-form .form-label .required {
            color: #b52f32;
            margin-left: 2px;
        }
        .register-form .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #0f172a;
            background: #f8fafc;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .register-form .form-control:focus {
            outline: none;
            border-color: #b52f32;
            box-shadow: 0 0 0 3px rgba(181,47,50,0.1);
            background: #ffffff;
        }
        .register-form select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        .register-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .register-form .btn-submit {
            width: 100%;
            padding: 11px 20px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            color: #ffffff;
            background: linear-gradient(135deg, #b52f32 0%, #8b1a1d 100%);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(181,47,50,0.3);
            margin-top: 4px;
        }
        .register-form .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(181,47,50,0.35);
        }
        .register-form .btn-submit:active {
            transform: translateY(0);
        }
        .register-form .btn-submit:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        .register-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #64748b;
        }
        .register-footer a {
            color: #b52f32;
            font-weight: 600;
            text-decoration: none;
        }
        .register-footer a:hover {
            text-decoration: underline;
        }
        .register-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
            padding: 14px 16px;
            background: #f1f5f9;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        .register-step {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            white-space: nowrap;
        }
        .register-step.active {
            color: #b52f32;
        }
        .register-step .step-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
            color: #94a3b8;
        }
        .register-step.active .step-dot {
            background: #b52f32;
            color: #ffffff;
        }
        .step-arrow {
            color: #cbd5e1;
            font-size: 12px;
        }
        .alert-banner {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .alert-banner.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-banner.success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .form-hint {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
        }
        @media (max-width: 480px) {
            .register-shell { padding: 24px 20px; }
            .register-form .form-row { grid-template-columns: 1fr; }
            .register-steps { flex-direction: column; gap: 4px; }
            .step-arrow { display: none; }
        }
    </style>
</head>
<body class="register-page">
    <div class="register-shell">
        <div class="logo-mark">
            <img src="{{ asset_versioned('logo.jpg') }}" alt="Hytec Power Inc.">
        </div>
        <p class="welcome-label">HYTEC POWER INC.</p>
        <h1>Create Account</h1>
        <p class="subtitle">Register to request an Access Code for booking appointments.</p>

        {{-- Progress Steps --}}
        <div class="register-steps">
            <div class="register-step active">
                <span class="step-dot">1</span>
                <span>Register</span>
            </div>
            <span class="step-arrow">→</span>
            <div class="register-step">
                <span class="step-dot">2</span>
                <span>Admin Approval</span>
            </div>
            <span class="step-arrow">→</span>
            <div class="register-step">
                <span class="step-dot">3</span>
                <span>Receive Code</span>
            </div>
        </div>

        @if(session('error'))
            <div class="alert-banner error">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert-banner success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert-banner error">
                <ul style="margin:0; padding-left:18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="register-form" id="registerForm">
            @csrf

            <div class="form-group">
                <label class="form-label" for="reg_name">Full Name <span class="required">*</span></label>
                <input class="form-control" id="reg_name" name="name" type="text" required autocomplete="name"
                       placeholder="e.g. Juan dela Cruz" value="{{ old('name') }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="reg_email">Email Address <span class="required">*</span></label>
                <input class="form-control" id="reg_email" name="email" type="email" required autocomplete="email"
                       placeholder="e.g. juan@company.com" value="{{ old('email') }}">
                <p class="form-hint">Your Access Code will be sent to this email once approved.</p>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="reg_phone">Phone Number <span class="required">*</span></label>
                    <input class="form-control" id="reg_phone" name="phone" type="tel" required autocomplete="tel"
                           placeholder="e.g. 0917-123-4567" value="{{ old('phone') }}">
                </div>

                <div class="form-group">
                    <label class="form-label" for="reg_department">Department <span class="required">*</span></label>
                    <select class="form-control" id="reg_department" name="department_id" required>
                        <option value="">— Select —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->department_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="btnRegister">
                Submit Registration
            </button>
        </form>

        <div class="register-footer">
            Already have an Access Code?
            <a href="{{ route('access') }}">Sign in here</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const nameInput = document.getElementById('reg_name');
            const emailInput = document.getElementById('reg_email');
            const phoneInput = document.getElementById('reg_phone');
            const deptSelect = document.getElementById('reg_department');
            const btnSubmit = document.getElementById('btnRegister');

            function checkFormValidity() {
                const isNameFilled = nameInput.value.trim().length > 0;
                const isEmailFilled = emailInput.value.trim().length > 0 && emailInput.checkValidity();
                const isPhoneFilled = phoneInput.value.trim().length > 0;
                const isDeptSelected = deptSelect.value.trim().length > 0;

                const allValid = isNameFilled && isEmailFilled && isPhoneFilled && isDeptSelected;
                btnSubmit.disabled = !allValid;
            }

            [nameInput, emailInput, phoneInput, deptSelect].forEach(function(el) {
                el.addEventListener('input', checkFormValidity);
                el.addEventListener('change', checkFormValidity);
            });

            // Initial state check
            checkFormValidity();

            form.addEventListener('submit', function(e) {
                if (!nameInput.value.trim() || !emailInput.value.trim() || !phoneInput.value.trim() || !deptSelect.value.trim()) {
                    e.preventDefault();
                    checkFormValidity();
                }
            });
        });
    </script>
</body>
</html>
