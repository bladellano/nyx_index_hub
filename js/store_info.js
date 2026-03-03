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
        let documentsHtml = '';

        if (data.data.documents && data.data.documents.length > 0) {
          documentsHtml = `
            <h4 style="margin-top: 20px; margin-bottom: 10px;">Documents (${data.data.documents.length})</h4>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
              <thead>
                <tr style="background: #e0e0e0;">
                  <th style="padding: 8px; text-align: left; border: 1px solid #ccc;">Display Name</th>
                  <th style="padding: 8px; text-align: left; border: 1px solid #ccc;">MIME Type</th>
                  <th style="padding: 8px; text-align: left; border: 1px solid #ccc;">Size</th>
                  <th style="padding: 8px; text-align: left; border: 1px solid #ccc;">Created</th>
                  <th style="padding: 8px; text-align: left; border: 1px solid #ccc;">Updated</th>
                  <th style="padding: 8px; text-align: center; border: 1px solid #ccc; width: 80px;">Actions</th>
                </tr>
              </thead>
              <tbody>
          `;

          data.data.documents.forEach(doc => {
            documentsHtml += `
              <tr style="background: #fff;">
                <td style="padding: 8px; border: 1px solid #ccc;" title="${doc.name}">${doc.displayName}</td>
                <td style="padding: 8px; border: 1px solid #ccc;">${doc.mimeType}</td>
                <td style="padding: 8px; border: 1px solid #ccc;">${doc.sizeBytes}</td>
                <td style="padding: 8px; border: 1px solid #ccc;">${doc.createTime}</td>
                <td style="padding: 8px; border: 1px solid #ccc;">${doc.updateTime}</td>
                <td style="padding: 8px; border: 1px solid #ccc; text-align: center;">
                  <button
                    onclick="deleteDocument('${doc.name}', '${storeName}', this)"
                    style="background: #dc3545; color: white; border: none; padding: 4px 8px; cursor: pointer; border-radius: 3px; font-size: 12px;"
                    title="Delete this document"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            `;
          });

          documentsHtml += `
              </tbody>
            </table>
          `;
        } else {
          documentsHtml = '<p style="margin-top: 15px; color: #666;"><em>No documents found in this store.</em></p>';
        }

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
            ${documentsHtml}
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

function deleteDocument(documentName, storeName, button) {
  if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
    return;
  }

  // Disable button and show loading state
  button.disabled = true;
  button.textContent = 'Deleting...';
  button.style.background = '#6c757d';

  // Send delete request
  fetch('/nyx/api/delete-document', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'document_name=' + encodeURIComponent(documentName) + '&store_name=' + encodeURIComponent(storeName)
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Show success message
        alert('Document deleted successfully!');

        // Reload store information to refresh the list
        const reloadButton = document.getElementById('load-store-info');
        if (reloadButton) {
          loadStoreInfo(reloadButton);
        }
      } else {
        alert('Error deleting document: ' + (data.message || 'Unknown error'));
        button.disabled = false;
        button.textContent = 'Delete';
        button.style.background = '#dc3545';
      }
    })
    .catch(error => {
      alert('Request failed: ' + error.message);
      button.disabled = false;
      button.textContent = 'Delete';
      button.style.background = '#dc3545';
    });
}
