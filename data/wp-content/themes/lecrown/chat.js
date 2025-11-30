(function () {
    const iframe = document.createElement('iframe');
    iframe.src = 'https://chat.askmortgageauthority.com/static/chat.html';
    iframe.id = 'mortgage-chatbot-iframe';
    iframe.className = 'chat-closed';

    // Append to wrapper, not body
    const wrapper = document.getElementById('chatbot-wrapper');
    wrapper.appendChild(iframe);

    // Listen for chat toggle command from the iframe
    window.addEventListener('message', function (event) {
        if (!event.data || typeof event.data !== 'object') return;
        const { type } = event.data;
        if (type === 'toggleChat') {
            wrapper.classList.toggle('chat-closed');
            wrapper.classList.toggle('chat-open');
            iframe.classList.toggle('chat-closed');
            iframe.classList.toggle('chat-open');
            const main = document.getElementById('primary');
            if (wrapper.classList.contains('chat-open')) {
                main.classList.add('chat-open');
                main.classList.remove('chat-closed');
            } else {
                main.classList.remove('chat-open');
                main.classList.add('chat-closed');
            }
        }
    });

    // Send parent URL to iframe after it loads
    iframe.onload = function () {
        iframe.contentWindow.postMessage({
            parentUrl: window.location.href
        }, '*');
    };
})();
