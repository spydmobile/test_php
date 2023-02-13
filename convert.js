// this nodejs code will read in a github workflow yaml file
// and output a mermaid code block saved to a markdown file.
const yaml = require('js-yaml');
const fs = require('fs');

// Get document, or throw exception on error
//iife
(async () => {
  const targets = [
       
   // { name: 'Versions', url: 'https://raw.githubusercontent.com/WISE-Developers/WISE_Versions/main/.github/workflows/release-trigger.yml' },
   //  { name: 'Builder', url: 'https://raw.githubusercontent.com/WISE-Developers/WISE_Builder_Component/main/.github/workflows/maven-publish.yml' },
    // { name: 'Manager', url: 'https://raw.githubusercontent.com/WISE-Developers/WISE_Manager_Component/main/.github/workflows/maven-publish.yml' },
    // { name: 'Application', url: 'https://raw.githubusercontent.com/WISE-Developers/WISE_Application/main/.github/workflows/cmake.yml' },
    // { name: 'JS API', url: 'https://raw.githubusercontent.com/WISE-Developers/WISE_JS_API/main/.github/workflows/archive.yml' },
     { name: 'Java API', url: 'https://raw.githubusercontent.com/WISE-Developers/WISE_Java_API/main/.github/workflows/maven-publish.yml' },
  ]
  
  
  for (const target of targets) {
    const workflowName = target.name;
    
    const workflowUrl = target.url;
     console.log(workflowName, "from ", workflowUrl);
    const workflowYaml = workflowUrl.split('.github/workflows/')[1];
    const workflowMarkdown = workflowName.toUpperCase().replace(/ /g, '_') + '.md';
    const workFlowRepo = workflowUrl.split('/.github/workflows/')[0].split('https://raw.githubusercontent.com/')[1];
    try {
      const response = await fetch(workflowUrl)
      const text = await response.text()
      const doc = yaml.load(text);
      let mermaidCode = `# ${doc.name} Workflow` + "\n" + "\n" + `## From ${workFlowRepo}` + "\n" + "\n" + "```" + "mermaid" + "\n" + "flowchart TB" + "\n";
      let stepIndex = 1
      const mermaidCodeEnd = "```" + "\n";;
      console.log(doc.jobs);

      const steps = (doc.jobs.build) ? doc.jobs.build.steps : (doc.jobs.trigger) ? doc.jobs.trigger.steps : []
    
      console.log("steps", steps.length);
      for (const step of steps) {
    
        // extract elements from each step.
        // console.log("step",step);
        let name = (step.name) ? step.name : step.uses.split('/')[1].split("@")[0];
        name = name.replaceAll('(', ' - ').replaceAll(')','');
        const uses = (step.uses) ? step.uses : step.run;
        const what = (uses.includes('checkout') || uses.includes('github-push-action')) ? (step.with.repository) ? step.with.repository : workFlowRepo : 'none';
    
        const block = `${stepIndex}[${name}]` + "\n";
        const connector = (what != 'none') ? `--${what}-->` + "\n" : `-->` + "\n";
        mermaidCode += block;
        mermaidCode += connector;
        //console.log({ name, uses });
        stepIndex += 1
    
        // foo
      }
      mermaidCode += `${stepIndex + 1}[Complete]` + "\n";
      mermaidCode += mermaidCodeEnd;
    
  
      //console.log(mermaidCode);
    
      fs.writeFile(workflowMarkdown, mermaidCode, (err) => {
        if (err)
          console.log(err);
        else {
          console.log(`${workflowMarkdown} written successfully`);
        }
      });
    
    } catch (e) {
      console.log(e);
    }
  }
  
})();




