<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Settings</title>
</head>
<body>
    <h1>Shipping Settings</h1>

    @if(session('success'))
        <div style="color: green;">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('shipping.save-settings') }}" method="POST">
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <input type="hidden" id="actionUrl" value="{{ route('shipping.save-settings') }}">
        
        <label for="shipping_name">Shipping Name:</label>
        <input type="text" id="shipping_name" name="shipping_name" value="{{ old('shipping_name', $shippingSettings->shipping_name ?? '') }}" required>

        <br><br>

        <label for="shipping_price">Shipping Price:</label>
        <input type="text" id="shipping_price" name="shipping_price" value="{{ old('shipping_price', $shippingSettings->shipping_price ?? '') }}" required>

        <br><br>

        <button type="submit">Save Settings</button>
    </form>

    <br>

    <!-- Add buttons to inject or remove the script -->
    <button id="injectScriptButton">Inject Script</button>
    <button id="removeScriptButton">Remove Script</button>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let form = document.querySelector("form");

            // Handle form submission for saving settings
            form.addEventListener("submit", function(event) {
                event.preventDefault();

                let formData = new FormData(form);
                let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch(form.action, {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(response => response.json())
                .then(data => {
                    alert("Settings saved successfully!");
                    location.reload();
                })
                .catch(error => console.error("Error:", error));
            });

            // Handle Inject Script Button click
            document.getElementById("injectScriptButton").addEventListener("click", function() {
              
                let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                fetch("{{ route('shipping.inject-script') }}", {
                    method: "GET",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Script injected successfully!");
                    } else {
                        alert("Failed to inject script.");
                    }
                })
                .catch(error => console.error("Error:", error));
            });

            // Handle Remove Script Button click
            document.getElementById("removeScriptButton").addEventListener("click", function() {
                let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                fetch("{{ route('shipping.remove-script') }}", {
                    method: "GET",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Script removed successfully!");
                    } else {
                        alert("Failed to remove script.");
                    }
                })
                .catch(error => console.error("Error:", error));
            });
        });
    </script>

</body>
</html>
