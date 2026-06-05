# -*- encoding: utf-8 -*-
# stub: ruby-xz 0.2.3 ruby lib

Gem::Specification.new do |s|
  s.name = "ruby-xz"
  s.version = "0.2.3"

  s.required_rubygems_version = Gem::Requirement.new(">= 0") if s.respond_to? :required_rubygems_version=
  s.require_paths = ["lib"]
  s.authors = ["Marvin G\u{fc}lker"]
  s.date = "2015-12-29"
  s.description = "These are simple Ruby bindings for the liblzma library\n(http://tukaani.org/xz/), which is best known for the\nextreme compression ratio its native XZ format achieves.\nSince FFI is used to implement the bindings, no compilation\nis needed and they should work with JRuby as well.\n"
  s.email = "quintus@quintilianus.eu"
  s.extra_rdoc_files = ["README.md", "HISTORY.rdoc", "COPYING", "AUTHORS"]
  s.files = ["AUTHORS", "COPYING", "HISTORY.rdoc", "README.md"]
  s.homepage = "http://quintus.github.io/ruby-xz"
  s.licenses = ["MIT"]
  s.rdoc_options = ["-t", "ruby-xz RDocs", "-m", "README.md"]
  s.required_ruby_version = Gem::Requirement.new(">= 1.9.3")
  s.rubygems_version = "2.5.1"
  s.summary = "XZ compression via liblzma for Ruby, using ffi."

  s.installed_by_version = "2.5.1" if s.respond_to? :installed_by_version

  if s.respond_to? :specification_version then
    s.specification_version = 4

    if Gem::Version.new(Gem::VERSION) >= Gem::Version.new('1.2.0') then
      s.add_runtime_dependency(%q<ffi>, ["~> 1.9"])
      s.add_runtime_dependency(%q<io-like>, ["~> 0.3"])
      s.add_development_dependency(%q<archive-tar-minitar>, ["~> 0.5"])
    else
      s.add_dependency(%q<ffi>, ["~> 1.9"])
      s.add_dependency(%q<io-like>, ["~> 0.3"])
      s.add_dependency(%q<archive-tar-minitar>, ["~> 0.5"])
    end
  else
    s.add_dependency(%q<ffi>, ["~> 1.9"])
    s.add_dependency(%q<io-like>, ["~> 0.3"])
    s.add_dependency(%q<archive-tar-minitar>, ["~> 0.5"])
  end
end
