<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Website Launch | Divine Taranga Silks</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Georgia', serif;
}

html, body {
    width: 100%;
    overflow-x: hidden;
}

body {
    background: url("images/launch-bg.png") no-repeat center center;
    background-size: cover;   /* show full image */
    background-color: #000;     /* fill empty space */
    min-height: 100svh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}
@media (max-width: 768px) {
    body {
        background-size: contain;
        background-position: top center;
    }
}

.overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1;
}

.content {
    position: relative;
    z-index: 2;
    text-align: center;
    width: 100%;
    max-width: 1000px;
    padding: 20px;
}

h1 {
    font-size: clamp(28px, 5vw, 52px);
    color: #f5d08a;
    letter-spacing: 2px;
    margin-bottom: 10px;
}

h2 {
    font-size: clamp(16px, 3vw, 22px);
    margin-bottom: 30px;
}

.countdown {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 40px;
}

.time-box {
    background: rgba(0,0,0,0.65);
    padding: 15px 20px;
    border-radius: 12px;
    min-width: 90px;
}

.time-box span {
    font-size: clamp(26px, 4vw, 38px);
    font-weight: bold;
    color: #f5d08a;
}

.time-box p {
    font-size: 12px;
    margin-top: 5px;
    letter-spacing: 1px;
}

@media (max-width: 768px) {
    body {
        background-position: center 20%;
    }
}
</style>
</head>

<body>

<div class="overlay"></div>

<div class="content">
    <!--<h1>Divine Taranga Silks</h1>-->
    <!--<h2>India’s Finest Real Gold & Silver Zari Silk Sarees</h2>-->

    <div class="countdown" id="countdown">
        <div class="time-box">
            <span id="days">00</span>
            <p>Days</p>
        </div>
        <div class="time-box">
            <span id="hours">00</span>
            <p>Hours</p>
        </div>
        <div class="time-box">
            <span id="minutes">00</span>
            <p>Minutes</p>
        </div>
        <div class="time-box">
            <span id="seconds">00</span>
            <p>Seconds</p>
        </div>
    </div>
</div>

<script>
    // Launch Date: 18 January 2026, 10:10 AM
    const launchDate = new Date(2026, 0, 18, 10, 10, 0).getTime();

    const timer = setInterval(() => {
        const now = new Date().getTime();
        const diff = launchDate - now;

        if (diff <= 0) {
            document.querySelector(".countdown").innerHTML =
                "<h2 style='color:#f5d08a;'>We Are Live!</h2>";
            clearInterval(timer);
            return;
        }

        document.getElementById("days").innerText = Math.floor(diff / (1000 * 60 * 60 * 24));
        document.getElementById("hours").innerText = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        document.getElementById("minutes").innerText = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        document.getElementById("seconds").innerText = Math.floor((diff % (1000 * 60)) / 1000);
    }, 1000);
</script>

</body>
</html>
