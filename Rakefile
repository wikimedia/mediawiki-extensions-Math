require 'bundler/setup'

def environments
  require 'yaml'
  environments = YAML::load_file(File.join(__dir__, 'tests/browser/environments.yml'))
end

def site(environment)
  environments[environment]['mediawiki_url'].split('/')[2]
end

def browser(environment)
  environments[environment]['browser'] || 'firefox'
end

desc 'beta'
task :beta do
  Dir.chdir('tests/browser') do
    sh "echo '--tags @#{site(ENV['jenkins'])} --tags @#{browser(ENV['jenkins'])}'"
  end
end

require 'rubocop/rake_task'
RuboCop::RakeTask.new(:rubocop) do |task|
  # if you use mediawiki-vagrant, rubocop will by default use it's .rubocop.yml
  # the next line makes it explicit that you want .rubocop.yml from the directory
  # where `bundle exec rake` is executed
  task.options = ['-c', '.rubocop.yml']
end

task default: [:test]

desc 'Run all build/tests commands (CI entry point)'
task test: [:rubocop]
