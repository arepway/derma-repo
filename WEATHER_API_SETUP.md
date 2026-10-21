# Weather API Setup Guide

## Using WeatherAPI.com

The weather widget now uses **WeatherAPI.com** for more accurate UV index, humidity, and weather data.

### Why WeatherAPI.com?

- **More accurate UV Index** - Real-time UV data included in every request
- **Better weather data** - More precise temperature and humidity readings
- **Generous free tier** - 1 million API calls per month (vs 1,000/day from others)
- **Simple API** - Single endpoint provides all data needed
- **Global coverage** - Accurate worldwide weather data

---

## Step 1: Get Your FREE API Key

1. **Go to WeatherAPI.com:**
   - Visit: https://www.weatherapi.com/signup.aspx

2. **Create Account:**
   - Enter your email
   - Create a password
   - Verify your email

3. **Get API Key:**
   - Log into your dashboard
   - Your API key will be displayed on the main page
   - Copy the key (looks like: `a1b2c3d4e5f6g7h8i9j0`)

---

## Step 2: Add API Key to Your System

1. **Open the patient dashboard file:**
   ```
   c:\xampp\htdocs\dermatology_clinic\patient_dashboard.php
   ```

2. **Find this line (around line 1779):**
   ```javascript
   const WEATHER_API_KEY = 'YOUR_WEATHERAPI_KEY_HERE';
   ```

3. **Replace with your actual key:**
   ```javascript
   const WEATHER_API_KEY = 'a1b2c3d4e5f6g7h8i9j0';
   ```

4. **Save the file**

---

## Step 3: Test the Widget

1. Open your browser
2. Go to: `http://localhost/dermatology_clinic/patient_dashboard.php`
3. Log in as a patient
4. Allow location access when prompted
5. The weather widget should display:
   - Current temperature
   - Weather condition
   - Humidity
   - Wind speed
   - **Accurate UV Index**
   - Personalized skincare tip

---

## Features

### Accurate UV Index
WeatherAPI.com provides real-time UV index data directly from their weather stations, not estimates. This gives patients accurate sun protection guidance.

### Improved Skincare Tips
The widget now provides:
- **40+ different skincare tips** based on conditions
- Tips for various combinations of:
  - UV levels (Low to Extreme)
  - Temperature ranges
  - Humidity levels (Very dry to Very humid)
  - Weather conditions (Rain, clouds, etc.)
- Random tip selection for variety

### Weather Conditions
Supports all weather types:
- Sunny/Clear
- Cloudy/Overcast
- Rain/Drizzle
- Thunderstorms
- Snow/Sleet
- Fog/Mist

---

## API Limits

### Free Tier (Plenty for clinics!)
- **1,000,000 calls/month** (about 33,000/day)
- Current refresh rate: every 30 minutes
- Even with 100 active patients = ~4,800 calls/day
- You'll never hit the limit!

### If You Need More
Paid plans available at reasonable prices:
- Developer: $4/month (unlimited calls)
- Business: Starting at $8/month

---

## Troubleshooting

### Widget Shows "Weather data unavailable"
1. **Check API key** - Make sure it's correctly pasted
2. **Check for typos** - No extra spaces or quotes
3. **Verify internet** - Make sure server has internet access
4. **Check browser console** (F12) for error messages

### Location Not Detected
- Browser may have blocked location access
- Widget will fall back to default city (Manila)
- Patient can still see weather data

### API Key Issues
- Make sure you're using the correct key from your dashboard
- Keys are case-sensitive
- Don't share your key publicly

### Check API Status
- Visit: https://www.weatherapi.com/docs/
- API status is shown at the top

---

## Customization

### Change Default City
In `patient_dashboard.php`, find `fetchWeatherByCity('Manila')` and change to your preferred city:
```javascript
fetchWeatherByCity('Your City');
```

### Change Refresh Rate
Find this line and adjust the interval (in milliseconds):
```javascript
setInterval(fetchWeatherData, 30 * 60 * 1000); // 30 minutes
```

For example, 15 minutes:
```javascript
setInterval(fetchWeatherData, 15 * 60 * 1000);
```

---

## Security Notes

- The API key is visible in client-side code
- For a clinic system, this is acceptable
- WeatherAPI.com free tier has no billing, so key exposure is low risk
- For production, consider:
  - Creating a PHP proxy endpoint
  - Using environment variables

---

## Support

- **WeatherAPI.com Documentation:** https://www.weatherapi.com/docs/
- **API Explorer:** https://www.weatherapi.com/api-explorer.aspx
- **Contact Support:** https://www.weatherapi.com/contact.aspx

---

## Quick Reference

**API Endpoint:**
```
https://api.weatherapi.com/v1/current.json?key=YOUR_KEY&q=LAT,LON&aqi=yes
```

**Response includes:**
- `current.temp_c` - Temperature in Celsius
- `current.feelslike_c` - Feels like temperature
- `current.humidity` - Humidity percentage
- `current.wind_kph` - Wind speed
- `current.uv` - UV Index (accurate!)
- `current.condition.text` - Weather description
- `location.name` - City name
- `location.country` - Country name
