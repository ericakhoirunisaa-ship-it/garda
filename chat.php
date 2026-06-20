<!DOCTYPE html>
<html lang="id">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-container { max-width: 600px; margin: 50px auto; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .chat-box { height: 400px; overflow-y: auto; padding: 20px; background: #f9f9f9; }
        .message { margin-bottom: 15px; padding: 10px; border-radius: 10px; }
        .ai-msg { background: #e3f2fd; border-left: 5px solid #2196f3; }
        .user-msg { background: #fff3e0; border-right: 5px solid #ff9800; text-align: right; }
    </style>
</head>
<body>

<div class="container">
    <div class="chat-container bg-white">
        <div class="p-3 border-bottom bg-primary text-white" style="border-radius: 15px 15px 0 0;">
            <h5 class="mb-0">Asisten KBLI Terpadu</h5>
        </div>
        <div id="chat-box" class="chat-box">
            <div class="message ai-msg">Halo! Ada yang bisa saya bantu terkait kode KBLI 2020?</div>
        </div>
        <div class="p-3 border-top">
            <div class="input-group">
                <input type="text" id="user-input" class="form-control" placeholder="Tulis usaha Anda...">
                <button class="btn btn-primary" onclick="sendChat()">Kirim</button>
            </div>
        </div>
    </div>
</div>

<script>
async function sendChat() {
    const inputField = document.getElementById('user-input');
    const chatBox = document.getElementById('chat-box');
    const question = inputField.value;

    if(!question) return;

    // Tampilkan pesan user
    chatBox.innerHTML += `<div class="message user-msg">${question}</div>`;
    inputField.value = '';

    // Panggil Backend Python
    const response = await fetch('http://localhost:8000/ask', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ question: question })
    });
    
    const data = await response.json();
    
    // Tampilkan jawaban AI
    chatBox.innerHTML += `<div class="message ai-msg">${data.answer}</div>`;
    chatBox.scrollTop = chatBox.scrollHeight;
}
</script>
</body>
</html>