function loadPage(page) {
    fetch(page)
        .then(response => response.text())
        .then(data => {
            document.getElementById('content-area').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('content-area').innerHTML = "<p>Error loading page.</p>";
        });
}
