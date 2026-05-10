<!DOCTYPE html>
<html>
<head>
    <title>TA AI Math Parser</title>
</head>
<body style="font-family: Arial; margin: 40px;">

    <h2>Mathematical Text Input</h2>

    <form method="POST" action="/process">
        @csrf

        <textarea name="text" rows="5" cols="50" placeholder="Masukkan soal matematika..."></textarea>
        <br><br>

        <button type="submit">Proses</button>
    </form>

    <hr>

    <h3>Hasil:</h3>

    @if(session('result'))
        <div style="padding:10px; background:#f0f0f0;">
            {{ session('result') }}
        </div>
    @endif

</body>
</html>