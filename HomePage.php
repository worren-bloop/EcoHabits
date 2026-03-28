<?php include __DIR__ . '/includes/cookie_consent.php'; ?>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | EcoHabits</title>
    <link rel="icon" type="image/png" href="assets/images/EcoHabits_logo.png">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.8;
    color: #333;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    background-color: #f8faf8;
    background-image: 
        radial-gradient(#1F8D49 0.5px, transparent 0.5px),
        radial-gradient(#1F8D49 0.5px, #f8faf8 0.5px);
    background-size: 20px 20px;
    background-position: 0 0, 10px 10px;
    background-attachment: fixed;
}

/* Section Titles */
.intro_title, .about_title, .goal_title {
    margin: 50px 0 20px;
    position: relative;
    padding-left: 15px;
}

.intro_title h2, .about_title h2, .goal_title h2 {
    color: #1F8D49;
    font-size: 32px;
    margin: 0;
    padding-bottom: 10px;
    display: inline-block;
    border-bottom: 3px solid #1F8D49;
}

.intro_title::before, .about_title::before, .goal_title::before {
    content: '';
    position: absolute;
    left: 0;
    top: 10px;
    height: 30px;
    width: 5px;
    background-color: #1F8D49;
    border-radius: 3px;
}

/* Content Sections */
.intro_content, .about_content {
    background-color: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.04);
    margin-bottom: 30px;
    line-height: 1.8;
    font-size: 17px;
    border-left: 4px solid #1F8D49;
}

.intro_content p, .about_content p {
    margin-bottom: 20px;
    color: #333;
}

.intro_content p:last-child {
    margin-bottom: 15px;
}

.intro_content ul {
    margin: 20px 0;
    padding-left: 25px;
}

.intro_content li {
    margin-bottom: 10px;
    position: relative;
    padding-left: 10px;
}

.intro_content li::before {
    content: '•';
    color: #1F8D49;
    font-weight: bold;
    position: absolute;
    left: -15px;
}

/* Goals Section */
.goal_content {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    margin: 20px 0 40px;
}

.goal_card {
    background-color: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    border-top: 4px solid #1F8D49;
    position: relative;
    overflow: hidden;
}

.goal_card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #1F8D49, #34A853);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.goal_card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 20px rgba(0,0,0,0.08);
}

.goal_card:hover::before {
    transform: scaleX(1);
}

.goal_card h3 {
    color: #1F8D49;
    margin-top: 0;
    font-size: 20px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.goal_card h3 span {
    margin-right: 10px;
}

.goal_card p {
    color: #555;
    font-size: 16px;
    line-height: 1.7;
}

#navigation {
  position: fixed;
  width: 200px;
  height: 300px;
  background-color: red;
  top: 0;
  left: -200px;
}

/* Responsive Design */
@media (max-width: 768px) {
    
    .intro_title h2, .about_title h2, .goal_title h2 {
        font-size: 26px;
    }

    .intro_content, .about_content {
        padding: 20px;
        font-size: 16px;
    }

    .goal_content {
        grid-template-columns: 1fr;
    }

    .goal_card {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .intro_title, .about_title, .goal_title {
        margin: 30px 0 15px;
    }

    .intro_title h2, .about_title h2, .goal_title h2 {
        font-size: 22px;
    }
}
    </style>
</head>
<body>
    

    <!-- intro -->
    <div class="intro_title"><h2>What is <b>CLIMATE CHANGE</b>?</h2></div>
    <div class="intro_content">
        <p>Climate change refers to significant, long-term changes in global temperature and weather patterns. 
            While climate has naturally fluctuated throughout Earth's history,
            human activities since the Industrial Revolution have dramatically accelerated these changes at an unprecedented rate.</p>
        
        <p>The primary driver of current climate change is the excessive emission of 
            greenhouse gases (GHGs) like carbon dioxide (CO₂) and methane (CH₄) from burning fossil fuels, 
            deforestation, and industrial agriculture. These gases trap heat in our atmosphere, causing the 
            "greenhouse effect" that leads to global warming.</p>
        
        <p>According to NASA, Earth's average temperature has risen by about 1.2°C since the late 19th century - 
            with the last decade (2011-2020) being the warmest on record. This warming is causing:</p>
        
        <ul>
            <li>More frequent and intense extreme weather events</li>
            <li>Rising sea levels from melting ice caps</li>
            <li>Ocean acidification</li>
            <li>Disruption of ecosystems and biodiversity loss</li>
        </ul>
    </div>

    <!-- about-->
    <div class="about_title"><h2>About us</h2></div>
    <div class="about_content">
        <p>Ecohabits is a passionate environmental initiative founded in 2024 to combat climate change through education and action. 
            We believe every individual's daily choices create ripple effects across our planet. 
            Our team of scientists, educators, and eco-activists works to translate complex climate science into actionable steps anyone can take.</p>
    
        <p>Born from the urgent need to address the climate crisis, Ecohabits bridges the gap 
            between awareness and real-world impact. We're not just another environmental blog - 
            we're a movement empowering people to build sustainable habits that collectively 
            transform our relationship with Earth.</p>   
     </div>

    <!--goal-->
    <div class="goal_title"><h2>Our goals</h2></div>
    <div class="goal_content">
        <div class="goal_card">
        <h3>🌱 Educate</h3>
        <p>Make climate science accessible to everyone through simple guides, interactive tools, and engaging content.</p>
    </div>
    
    <div class="goal_card">
        <h3>♻️ Empower</h3>
        <p>Provide practical tools like our Carbon Footprint Calculator and 30-Day Eco-Challenges to drive real change.</p>
    </div>
    
    <div class="goal_card">
        <h3>✊ Mobilize</h3>
        <p>Connect 1 million climate activists by 2025 through our petition platforms and local action networks.</p>
    </div>
    
    <div class="goal_card">
        <h3>💡 Innovate</h3>
        <p>Develop AI-powered tools like our "Eco-Assistant" chatbot to personalize sustainability journeys.</p>
    </div>
    </div>
</body>
</html>