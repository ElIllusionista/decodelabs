const express = require('express');
const cors = require('cors');

const app = express();

app.use(cors());
const fs = require('fs');

const DATA_FILE = './items.json';

const PORT = 3000;

app.use(express.json());

let items = [];
try {
  const fileContent = fs.readFileSync(DATA_FILE, 'utf8');
  items = JSON.parse(fileContent);
} catch (err) {
  console.log('No existing items.json found, starting with an empty list.');
}

function saveItems() {
  fs.writeFileSync(DATA_FILE, JSON.stringify(items, null, 2));
}

app.get('/', (req, res) => {
  res.send('Hello from DecodeLabs API!');
});
app.get('/items', (req, res) => {
  res.json(items);
});

app.get('/items/:id', (req, res) => {
  const id = parseInt(req.params.id);

  const item = items.find(i => i.id === id);

  if (!item) {
    return res.status(404).json({ error: 'Item not found' });
  }

  res.json(item);
});

app.post('/items', (req, res) => {
  const { name } = req.body;

  if (!name || typeof name !== 'string' || name.trim() === '') {
    return res.status(400).json({ error: 'Name is required and must be a non-empty string' });
  }

  const newItem = {
    id: items.length > 0 ? Math.max(...items.map(i => i.id)) + 1 : 1,
    name: name.trim(),
    done: false
  };

  items.push(newItem);
  saveItems();
  res.status(201).json(newItem);
});

app.put('/items/:id', (req, res) => {
  const id = parseInt(req.params.id);
  const item = items.find(i => i.id === id);

  if (!item) {
    return res.status(404).json({ error: 'Item not found' });
  }

  const { name, done } = req.body;

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

app.delete('/items/:id', (req, res) => {
  const id = parseInt(req.params.id);
  const index = items.findIndex(i => i.id === id);

  if (index === -1) {
    return res.status(404).json({ error: 'Item not found' });
  }

  const deleted = items.splice(index, 1)[0];
  saveItems();

  res.json({ message: 'Item deleted', item: deleted });
});

app.listen(PORT, () => {
  console.log(`Server running at http://localhost:${PORT}`);
});
