const yaml = require('js-yaml');
const fs   = require('fs');

// Get document, or throw exception on error
try {
  const doc = yaml.load(fs.readFileSync('data/foo.yml', 'utf8'));
  console.log(doc);
} catch (e) {
  console.log(e);
}

const steps = doc.jobs.build.steps

for (const step of steps) {

  // extract elements from each step.
  const name = step.name
  const run = step.run
  
  
}