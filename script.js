function showConfirmationMessage() {
    const message = document.getElementById('confirmationMessage');
    if (message) {
        message.classList.add('show');
        // Ocultar el mensaje después de 5 segundos
        setTimeout(() => {
            message.classList.remove('show');
        }, 5000);
    }
}