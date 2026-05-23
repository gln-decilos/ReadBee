const selectAll = document.getElementById('select-all');
const deleteControls = document.getElementById('delete-controls');
const addButton = document.getElementById('add-button');
const addForm = document.getElementById('add-form');
const readingTable = document.getElementById('reading-table');

function getCheckboxes() {
  return document.querySelectorAll('tbody .row-checkbox');
}

function updateActionControls() {
  const anyChecked = Array.from(getCheckboxes()).some(cb => cb.checked);
  if (anyChecked) {
    addButton.classList.add('hidden');
    deleteControls.classList.remove('hidden');
  } else {
    addButton.classList.remove('hidden');
    deleteControls.classList.add('hidden');
  }
}

function colorStatusCells() {
  const statusCells = document.querySelectorAll('.status-cell');
  statusCells.forEach(cell => {
    const status = cell.textContent.trim().toLowerCase();
    let badgeClass = '';
    let text = '';
    if (status === 'approved') {
      badgeClass = 'badge badge-success';
      text = 'Approved';
    } else if (status === 'not approved' || status === 'not_approved') {
      badgeClass = 'badge badge-danger';
      text = 'Not Approved';
    } else {
      badgeClass = 'badge badge-secondary';
      text = status.charAt(0).toUpperCase() + status.slice(1);
    }
    cell.innerHTML = `<span class="${badgeClass}">${text}</span>`;
  });
}

selectAll.addEventListener('change', function () {
  getCheckboxes().forEach(cb => (cb.checked = this.checked));
  updateActionControls();
});

document.addEventListener('change', function (e) {
  if (e.target.classList.contains('row-checkbox')) {
    updateActionControls();
  }
});

document.getElementById('delete-selected').addEventListener('click', function () {
  const selected = Array.from(document.querySelectorAll('.row-checkbox'))
    .filter(cb => cb.checked)
    .map(cb => cb.value);

  if (selected.length === 0) {
    alert("Please select at least one material to delete.");
    return;
  }

  if (!confirm("Are you sure you want to delete the selected materials?")) return;

  fetch('Principal_functions/delete_reading_material.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids: selected })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        selected.forEach(id => {
          const checkbox = document.querySelector(`.row-checkbox[value="${id}"]`);
          if (checkbox) {
            const row = checkbox.closest('tr');
            if (row) row.remove();
          }
        });
        alert("Selected materials deleted successfully.");
      } else {
        alert("Failed to delete: " + data.message);
      }
    })
    .catch(error => {
      console.error("Delete error:", error);
      alert("An error occurred while deleting.");
    });
});

// ✅ Only one status change event listener (the correct one)
document.getElementById('status-action').addEventListener('change', function () {
  const newStatus = this.value;
  const selected = Array.from(getCheckboxes())
    .filter(cb => cb.checked)
    .map(cb => cb.value);

  if (!newStatus || newStatus === "") {
    alert("Please choose a valid status.");
    this.selectedIndex = 0;
    return;
  }

  if (selected.length === 0) {
    alert("Please select at least one material to update.");
    this.selectedIndex = 0;
    return;
  }

  if (!confirm(`Are you sure you want to update the status to "${newStatus}" for the selected materials?`)) {
    this.selectedIndex = 0;
    return;
  }

  fetch('Principal_functions/update_reading_material_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids: selected, status: newStatus })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        selected.forEach(id => {
          const checkbox = document.querySelector(`.row-checkbox[value="${id}"]`);
          if (checkbox) {
            const statusCell = checkbox.closest('tr').querySelector('.status-cell');
            if (statusCell) {
              statusCell.innerHTML = `<span class="badge ${
                newStatus.toLowerCase() === 'approved' ? 'badge-success' :
                newStatus.toLowerCase() === 'not_approved' ? 'badge-danger' : 
                'badge-secondary'
              }">${newStatus.replace('_', ' ')}</span>`;
            }
          }
        });
        alert("Status updated successfully.");
      } else {
        alert("Failed to update status: " + data.message);
      }
      document.getElementById('status-action').selectedIndex = 0;
    })
    .catch(error => {
      console.error("Status update error:", error);
      alert("An error occurred while updating status.");
      document.getElementById('status-action').selectedIndex = 0;
    });
});

function toggleStatusDropdown() {
  const dropdown = document.getElementById('status-filter-dropdown');
  dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function applyStatusFilter() {
  const checkboxes = document.querySelectorAll('#status-filter-dropdown input[type="checkbox"]');
  const selectedValues = Array.from(checkboxes)
    .filter(cb => cb.checked)
    .map(cb => cb.value.toLowerCase());

  const rows = document.querySelectorAll('tbody tr');
  rows.forEach(row => {
    const statusCell = row.querySelector('.status-cell');
    const statusText = statusCell.textContent.toLowerCase();
    row.style.display = selectedValues.includes(statusText) ? '' : 'none';
  });
}

window.addEventListener('click', function (e) {
  if (!e.target.matches('.filter-icon')) {
    const dropdown = document.getElementById('status-filter-dropdown');
    dropdown.style.display = 'none';
  }
});

window.addEventListener('DOMContentLoaded', () => {
  colorStatusCells();
  applyStatusFilter();
});

addButton.addEventListener('click', function () {
  addForm.classList.remove('hidden');
  readingTable.classList.add('hidden');
  selectAll.checked = false;
  updateActionControls();
});

function cancelAddForm() {
  addForm.classList.add('hidden');
  readingTable.classList.remove('hidden');
}

