
function generateQr(elem, text) {
    const qrcodeElement = document.getElementById(elem);
    
    // Clear previous QR code if it exists
    qrcodeElement.innerHTML = "";
    
    if (text.trim() !== '') {
        // Generate the QR code
        new QRCode(qrcodeElement, {
            text: text,
            width: 200,
            height: 200,
            colorDark : "#333333",
            colorLight : "#FFFFFF",
            correctLevel : QRCode.CorrectLevel.H
        });
    } else {
        qrcodeElement.innerHTML = "<p>Please enter some text to generate a QR code.</p>";
    }
}
                    
// Generate a QR code on page load with the default value
//window.onload = generateQr;