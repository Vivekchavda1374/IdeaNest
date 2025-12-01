// End-to-End Encryption for Chat System
class ChatEncryption {
    constructor() {
        this.algorithm = 'AES-GCM';
        this.keyLength = 256;
    }

    async generateKeyPair() {
        const key = await window.crypto.subtle.generateKey(
            { name: this.algorithm, length: this.keyLength },
            true,
            ['encrypt', 'decrypt']
        );
        return key;
    }

    async encryptMessage(message, key) {
        const encoder = new TextEncoder();
        const data = encoder.encode(message);
        const iv = window.crypto.getRandomValues(new Uint8Array(12));
        
        const encryptedData = await window.crypto.subtle.encrypt(
            { name: this.algorithm, iv: iv },
            key,
            data
        );

        return {
            encrypted: this.arrayBufferToBase64(encryptedData),
            iv: this.arrayBufferToBase64(iv)
        };
    }

    async decryptMessage(encryptedMessage, iv, key) {
        try {
            if (!encryptedMessage || !iv || !key) {
                console.error('Missing decryption parameters');
                return 'Message';
            }
            
            const encryptedData = this.base64ToArrayBuffer(encryptedMessage);
            const ivArray = this.base64ToArrayBuffer(iv);

            const decryptedData = await window.crypto.subtle.decrypt(
                { name: this.algorithm, iv: ivArray },
                key,
                encryptedData
            );

            const decoder = new TextDecoder();
            return decoder.decode(decryptedData);
        } catch (e) {
            console.error('Decryption error:', e);
            return 'Message';
        }
    }

    async exportKey(key) {
        const exported = await window.crypto.subtle.exportKey('raw', key);
        return this.arrayBufferToBase64(exported);
    }

    async importKey(keyData) {
        const keyBuffer = this.base64ToArrayBuffer(keyData);
        return await window.crypto.subtle.importKey(
            'raw',
            keyBuffer,
            { name: this.algorithm, length: this.keyLength },
            true,
            ['encrypt', 'decrypt']
        );
    }

    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }

    base64ToArrayBuffer(base64) {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    async storeKey(conversationId, key) {
        const exported = await this.exportKey(key);
        sessionStorage.setItem(`chat_key_${conversationId}`, exported);
    }

    async retrieveKey(conversationId) {
        const keyData = sessionStorage.getItem(`chat_key_${conversationId}`);
        if (!keyData) return null;
        return await this.importKey(keyData);
    }

    async getOrCreateConversationKey(conversationId, serverKey = null) {
        let key = await this.retrieveKey(conversationId);
        if (key) return key;
        
        if (serverKey) {
            try {
                key = await this.importKey(serverKey);
                await this.storeKey(conversationId, key);
                return key;
            } catch (e) {
                console.error('Failed to import server key:', e);
            }
        }
        
        key = await this.generateKeyPair();
        await this.storeKey(conversationId, key);
        return key;
    }
}

const chatEncryption = new ChatEncryption();
