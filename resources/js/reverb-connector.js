export default class ReverbConnector {
    constructor(options) {
        this.options = options;
        this.connect();
    }

    connect() {
        this.socket = new WebSocket(
            `${this.options.scheme}://${this.options.host}:${this.options.port}`
        );

        this.socket.onopen = () => {
            console.log('Reverb WebSocket connection established');
        };

        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            console.log('Message received:', data);
        };

        this.socket.onclose = () => {
            console.log('Reverb WebSocket connection closed');
        };
    }

    listen(channel, event, callback) {
        this.socket.send(JSON.stringify({
            event: 'subscribe',
            channel: channel,
        }));

        this.socket.onmessage = (message) => {
            const data = JSON.parse(message.data);
            if (data.event === event) {
                callback(data.payload);
            }
        };
    }
}