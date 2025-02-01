import { initializeApp } from "https://www.gstatic.com/firebasejs/11.2.0/firebase-app.js";

const firebaseConfig = {
    apiKey: "AIzaSyBAP1ssrBHZBEKdDM0J63BdS7CPFtf72-U",
    authDomain: "skillswap-8e3eb.firebaseapp.com",
    projectId: "skillswap-8e3eb",
    storageBucket: "skillswap-8e3eb.firebasestorage.app",
    messagingSenderId: "149938196544",
    appId: "1:149938196544:web:03d701864f3d09e7568fa5"
  };


// Initialise Firebase
const app = initializeApp(firebaseConfig);
const database = firebase.database();
const messaging = firebase.messaging();

// 🔥 Fonction pour écouter les nouveaux messages en temps réel
function listenForMessages(userId) {
    const chatRef = database.ref("messages/" + userId);
    chatRef.on("child_added", function(snapshot) {
        const message = snapshot.val();
        alert("📩 Nouveau message : " + message.content);
    });
}

// 🔥 Fonction pour envoyer un message
function sendMessage(senderId, receiverId, content) {
    database.ref("messages/" + receiverId).push({
        senderId: senderId,
        content: content,
        timestamp: new Date().toISOString()
    });
}

export { sendMessage, listenForMessages };
