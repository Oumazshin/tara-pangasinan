# Tara Pangasinan

Welcome to **Tara Pangasinan**, a dynamic tourism website designed to showcase the unparalleled beauty, vibrant culture, and hidden wonders of Pangasinan, Philippines. 

## 🌟 Features

- **Explore Destinations**: Browse curated lists of nature parks, pristine beaches, historic sites, festivals, and hidden gems.
- **Guided Tours Booking**: Choose from island hopping, cave exploring, and beach tours using an easy 4-step booking wizard.
- **Personalized Experience**: Create an account, save your favorite spots, and build your personal travel list.
- **Responsive Design**: Beautiful, modern, and fully responsive layout built with custom CSS.

## 🛠️ Technologies Used

- **HTML5**: For semantic structure and content layout.
- **CSS3**: Vanilla CSS for styling, utilizing modern design aesthetics, responsive layouts, and interactive animations.
- **JavaScript**: For dynamic interactivity, booking logic, and seamless navigation.
- **Google Fonts**: Utilizing 'Inter' and 'Outfit' for crisp, modern typography.

## 🚀 How to Run Locally

Since this is a vanilla frontend project (HTML, CSS, JavaScript) without complex build steps, running it locally is very straightforward.

### Method 1: Using a Local Web Server (Recommended)

Using a local server ensures that all assets and potential fetch requests load correctly without CORS issues.

1. **VS Code Live Server Extension**:
   - Open the project folder in Visual Studio Code.
   - Install the **Live Server** extension if you haven't already.
   - Right-click on `index.html` in the file explorer and select **"Open with Live Server"**.
   - Your default browser will automatically open the project (usually at `http://127.0.0.1:5500`).

2. **Using Python**:
   - Open your terminal or command prompt in the project directory.
   - Run the following command:
     ```bash
     python -m http.server
     ```
   - Open your browser and navigate to `http://localhost:8000`.

3. **Using Node.js (`serve`)**:
   - If you have Node.js installed, you can use the `serve` package:
     ```bash
     npx serve .
     ```
   - Open your browser and navigate to the provided local address (usually `http://localhost:3000`).

### Method 2: Direct File Open

- Simply navigate to the project folder (`c:\Users\Desktop\Documents\GitHub\Tara Pangasinan`) on your computer.
- Double-click the `index.html` file to open it directly in your default web browser.

## 📁 Project Structure

- `index.html`: The landing page and main entry point.
- `home.html`, `explore.html`, `plan.html`, etc.: Additional application pages.
- `css/`: Contains all stylesheets for the project.
- `js/`: JavaScript files for interactivity and business logic.
- `assets/`: Images, icons, and other media assets.
- `data/`: JSON or static data files used to populate the site.
- `docs/`: Additional documentation files.
