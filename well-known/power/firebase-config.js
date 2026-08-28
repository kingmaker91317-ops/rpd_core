// firebase-config.js
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.9.1/firebase-app.js";
import { getAnalytics } from "https://www.gstatic.com/firebasejs/11.9.1/firebase-analytics.js";
import { getDatabase } from "https://www.gstatic.com/firebasejs/11.9.1/firebase-database.js";

const firebaseConfig = {
  apiKey: "AIzaSyBUfGGI3MhgzMmMmRiOETZygl82YF6gZsI",
  authDomain: "nexpanel-d93f6.firebaseapp.com",
  databaseURL: "https://nexpanel-d93f6-default-rtdb.firebaseio.com",
  projectId: "nexpanel-d93f6",
  storageBucket: "nexpanel-d93f6.firebasestorage.app",
  messagingSenderId: "674567456354",
  appId: "1:674567456354:web:4e528b60619ec8b911733b",
  measurementId: "G-WJRRFR2VJ4"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);
const db = getDatabase(app);

// Export the initialized services
export { app, analytics, db };