const express = require('express');
// Import cors - allows our frontend webpage to talk to this API
const cors = require('cors');

// Create an "app" - this is our server object, we'll attach routes to it
const app = express();

// Allow requests from webpages (browsers block cross-origin requests by default)
app.use(cors());
// fs = Node's built-in "file system" module, lets us read/write files
const fs = require('fs');

// Path to our data file
const DATA_FILE = './items.json';

// Pick a port number - this is like a "channel" your server listens on
const PORT = 3000;

// Middleware: lets Express understand JSON sent in request bodies (needed for POST/PUT later)
app.use(express.json());

// Our "database" for now - just an array living in memory
// Each object has an id, a name, and a done status
// Load items from the JSON file when the server starts
// If the file doesn't exist yet or is invalid, fall back to an empty array
let items = [];
try {
  const fileContent = fs.readFileSync(DATA_FILE, 'utf8');
  items = JSON.parse(fileContent);
} catch (err) {
  console.log('No existing items.json found, starting with an empty list.');
}

// Helper function: saves the current items array to the JSON file
// We call this after every change (add, update, delete)
function saveItems() {
  fs.writeFileSync(DATA_FILE, JSON.stringify(items, null, 2));
}

// Define a route: when someone sends a GET request to "/", run this function
app.get('/', (req, res) => {
  // req = the incoming request (data from whoever called this)
  // res = the response we send back
  res.send('Hello from DecodeLabs API!');
});
// GET /items - returns the full list of items
app.get('/items', (req, res) => {
  res.json(items);
});

// GET /items/:id - returns a single item matching the given id
app.get('/items/:id', (req, res) => {
  // req.params holds values from the URL itself (the ":id" part)
  // URL params always arrive as strings, so we convert to a number
  const id = parseInt(req.params.id);

  // Search our array for an item whose id matches
  const item = items.find(i => i.id === id);

  // If nothing was found, item will be "undefined"
  if (!item) {
    // 404 = "Not Found" - a standard HTTP status code
    return res.status(404).json({ error: 'Item not found' });
  }

  // Otherwise, send back the item we found
  res.json(item);
});

// POST /items - creates a new item
app.post('/items', (req, res) => {
  // req.body holds the JSON data sent by whoever called this
  // e.g. { "name": "Buy milk" }
  const { name } = req.body;

  // Basic validation: name must exist and be a non-empty string
  if (!name || typeof name !== 'string' || name.trim() === '') {
    // 400 = "Bad Request" - the client sent invalid data
    return res.status(400).json({ error: 'Name is required and must be a non-empty string' });
  }

  // Create a new item object
  // We generate a new id by taking the highest existing id and adding 1
  const newItem = {
    id: items.length > 0 ? Math.max(...items.map(i => i.id)) + 1 : 1,
    name: name.trim(),
    done: false
  };

  // Add it to our in-memory array
  items.push(newItem);
  saveItems();

  // 201 = "Created" - standard status code for successful creation
  res.status(201).json(newItem);
});

// PUT /items/:id - updates an existing item
app.put('/items/:id', (req, res) => {
  const id = parseInt(req.params.id);
  const item = items.find(i => i.id === id);

  if (!item) {
    return res.status(404).json({ error: 'Item not found' });
  }

  const { name, done } = req.body;

  // Only update fields that were actually provided
  if (name !== undefined) {
    if (typeof name !== 'string' || name.trim() === '') {
      return res.status(400).json({ error: 'Name must be a non-empty string' });
    }
    item.name = name.trim();
  }

  if (done !== undefined) {
    if (typeof done !== 'boolean') {
      return res.status(400).json({ error: 'Done must be true or false' });
    }
    item.done = done;
  }
  saveItems();
  res.json(item);
});

// DELETE /items/:id - removes an item
app.delete('/items/:id', (req, res) => {
  const id = parseInt(req.params.id);
  const index = items.findIndex(i => i.id === id);

  if (index === -1) {
    return res.status(404).json({ error: 'Item not found' });
  }

  // Remove the item from the array and store what we removed
  const deleted = items.splice(index, 1)[0];
  saveItems();

  res.json({ message: 'Item deleted', item: deleted });
});

// Start the server, listening on our chosen port
app.listen(PORT, () => {
  console.log(`Server running at http://localhost:${PORT}`);
});