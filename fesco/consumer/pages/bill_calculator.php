<h2>Bill Calculator</h2>
<form>
    <label for="units">Enter Units:</label>
    <input type="number" id="units" name="units" required>
    <button type="button" onclick="calculateBill()">Calculate</button>
</form>
<p id="bill-result"></p>

<script>
function calculateBill() {
    let units = document.getElementById('units').value;
    let rate = 36; // Example: Rs 10 per unit
    let total = units * rate;
    document.getElementById('bill-result').innerText = "Your estimated bill is: Rs " + total;
}
</script>
