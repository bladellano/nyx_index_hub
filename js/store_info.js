function loadStoreInfo(button) {
  const storeName = button.getAttribute('data-store-name');
  const resultDiv = document.getElementById('store-info-result');

  // Show loading
  button.disabled = true;
  button.textContent = 'Loading...';
  resultDiv.innerHTML = '<p>Loading store information...</p>';

  // Fetch data
  fetch('/nyx/api/store-info?store_name=' + encodeURIComponent(storeName))
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        resultDiv.innerHTML = `
          <div style="border: 1px solid #ddd; padding: 15px; margin-top: 10px; background: #f9f9f9;">
            <h3>Store Information</h3>
            <div style="display: grid; grid-template-columns: 200px 1fr; gap: 10px;">
              <strong>Display Name:</strong><span>${data.data.displayName}</span>
              <strong>Active Documents:</strong><span>${data.data.activeDocuments}</span>
              <strong>Size:</strong><span>${data.data.sizeBytes}</span>
              <strong>Created:</strong><span>${data.data.createTime}</span>
              <strong>Updated:</strong><span>${data.data.updateTime}</span>
            </div>
          </div>
        `;
        button.textContent = 'Reload Store Information';
      } else {
        resultDiv.innerHTML = '<p style="color: red;">Error: ' + (data.message || 'Unknown error') + '</p>';
        button.textContent = 'Try Again';
      }
      button.disabled = false;
    })
    .catch(error => {
      resultDiv.innerHTML = '<p style="color: red;">Request failed: ' + error.message + '</p>';
      button.textContent = 'Try Again';
      button.disabled = false;
    });
}
